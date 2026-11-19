<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Services;

use App\Modules\Downloads\Models\DownloadItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * Servicio de bóveda segura para archivos descargables.
 *
 * Almacena todos los paquetes fuera de `public/` con nombres UUID v4 ofuscados,
 * valida magic bytes reales, inspecciona paquetes ZIP contra ataques Zip Slip
 * y Zip Bomb, y calcula la huella criptográfica SHA-256.
 */
class DownloadVaultService
{
    /**
     * Procesa, inspecciona y almacena de forma segura un archivo subido.
     *
     * @return array{
     *     vault_disk: string,
     *     private_file_path: string,
     *     original_filename: string,
     *     mime_type: string,
     *     file_size_bytes: int,
     *     sha256_hash: string
     * }
     */
    public function storeUploadedPackage(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        if ($realPath === false || !is_readable($realPath)) {
            throw ValidationException::withMessages([
                'package_file' => 'No se pudo leer el archivo temporal subido.',
            ]);
        }

        // 1. Validar tamaño máximo permitido desde configuración
        $maxBytes = ((int) config('downloads.vault.max_upload_kb', 102400)) * 1024;
        $sizeBytes = (int) filesize($realPath);
        if ($sizeBytes <= 0 || $sizeBytes > $maxBytes) {
            throw ValidationException::withMessages([
                'package_file' => 'El tamaño del paquete no es válido o supera el límite permitido.',
            ]);
        }

        // 2. Validar extensión contra lista blanca configurada
        $allowedExtensions = array_map('strtolower', (array) config('downloads.vault.allowed_extensions', ['zip']));
        $clientExt = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($clientExt, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'package_file' => 'Extensión de archivo no permitida. Solo se admiten paquetes: .' . implode(', .', $allowedExtensions),
            ]);
        }

        // 3. Validar MIME real mediante inspección de Magic Bytes (finfo)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = strtolower((string) $finfo->file($realPath));
        $allowedMimes = array_map('strtolower', (array) config('downloads.vault.allowed_mimes', ['application/zip']));
        if (!in_array($detectedMime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'package_file' => "El contenido binario real del archivo ({$detectedMime}) no corresponde a un paquete válido.",
            ]);
        }

        // 4. Si es un archivo ZIP, inspeccionar estructura interna contra Zip Slip y Zip Bombs
        if ($clientExt === 'zip' || str_contains($detectedMime, 'zip')) {
            $this->inspectZipPackage($realPath, $sizeBytes);
        }

        // 5. Calcular huella de integridad SHA-256 en streaming
        $sha256 = hash_file('sha256', $realPath);
        if ($sha256 === false) {
            throw ValidationException::withMessages([
                'package_file' => 'No fue posible calcular la firma de integridad SHA-256 del paquete.',
            ]);
        }

        // 6. Almacenar en la bóveda privada con nombre ofuscado UUID v4 particionado por año/mes
        $diskName = (string) config('downloads.vault.disk', 'downloads_vault');
        $directory = now()->format('Y/m');
        $obscuredName = Str::uuid()->toString() . '.bin';
        $storedPath = Storage::disk($diskName)->putFileAs($directory, $file, $obscuredName, ['visibility' => 'private']);

        if (!$storedPath) {
            throw ValidationException::withMessages([
                'package_file' => 'Error interno al guardar el paquete en la bóveda privada.',
            ]);
        }

        return [
            'vault_disk'        => $diskName,
            'private_file_path' => $storedPath,
            'original_filename' => $this->sanitizeOriginalFilename($file->getClientOriginalName(), $clientExt),
            'mime_type'         => $detectedMime,
            'file_size_bytes'   => $sizeBytes,
            'sha256_hash'       => $sha256,
        ];
    }

    /**
     * Elimina de forma segura el archivo físico asociado a un ítem si existe en la bóveda.
     */
    public function deleteStoredPackage(?string $diskName, ?string $privatePath): void
    {
        if (empty($privatePath)) {
            return;
        }

        $targetDisk = $diskName ?: (string) config('downloads.vault.disk', 'downloads_vault');

        try {
            if (Storage::disk($targetDisk)->exists($privatePath)) {
                Storage::disk($targetDisk)->delete($privatePath);
            }
        } catch (\Throwable) {
            // Fallo silencioso en limpieza física para no bloquear transacciones si el disco cambió
        }
    }

    /**
     * Elimina el archivo físico asociado a un modelo DownloadItem.
     */
    public function purgeItemFile(DownloadItem $item): void
    {
        $this->deleteStoredPackage($item->vault_disk, $item->private_file_path);
    }

    /**
     * Inspecciona entradas internas de un archivo ZIP para bloquear Zip Slip y Zip Bombs.
     */
    private function inspectZipPackage(string $realPath, int $compressedSize): void
    {
        $zip = new ZipArchive();
        $openStatus = $zip->open($realPath, ZipArchive::RDONLY);

        if ($openStatus !== true) {
            throw ValidationException::withMessages([
                'package_file' => 'El archivo ZIP está corrupto o no tiene una estructura válida.',
            ]);
        }

        $maxEntries = (int) config('downloads.zip_security.max_entries', 3000);
        $maxUncompressed = (int) config('downloads.zip_security.max_uncompressed_bytes', 524288000);
        $maxRatio = (int) config('downloads.zip_security.max_compression_ratio', 100);

        try {
            if ($zip->numFiles <= 0) {
                throw ValidationException::withMessages([
                    'package_file' => 'El archivo ZIP está vacío.',
                ]);
            }

            if ($zip->numFiles > $maxEntries) {
                throw ValidationException::withMessages([
                    'package_file' => "El paquete excede el número máximo de archivos internos permitidos ({$maxEntries}).",
                ]);
            }

            $totalUncompressed = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (!is_array($stat) || !isset($stat['name'])) {
                    continue;
                }

                $entryName = (string) $stat['name'];

                // Defensa contra Zip Slip (traversal de directorios, bytes nulos o rutas absolutas)
                $normalized = str_replace('\\', '/', $entryName);
                if (
                    str_contains($entryName, "\0") ||
                    str_starts_with($normalized, '/') ||
                    preg_match('#^[a-zA-Z]:/#', $normalized) ||
                    in_array('..', explode('/', $normalized), true)
                ) {
                    throw ValidationException::withMessages([
                        'package_file' => 'Paquete rechazado por seguridad: contiene rutas internas potencialmente peligrosas (Zip Slip).',
                    ]);
                }

                $entrySize = max(0, (int) ($stat['size'] ?? 0));
                $totalUncompressed += $entrySize;

                if ($totalUncompressed > $maxUncompressed) {
                    throw ValidationException::withMessages([
                        'package_file' => 'Paquete rechazado por seguridad: el tamaño descomprimido supera el límite permitido.',
                    ]);
                }
            }

            if ($compressedSize > 0 && ($totalUncompressed / $compressedSize) > $maxRatio && $totalUncompressed > 10485760) {
                throw ValidationException::withMessages([
                    'package_file' => 'Paquete rechazado por seguridad: ratio de compresión anómalo (posible Zip Bomb).',
                ]);
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * Limpia el nombre original del archivo para cabeceras HTTP Content-Disposition seguras,
     * preservando guiones bajos y numeración semántica de versiones (ej. `plg_system_fv_hero-v1.0.0.zip`).
     */
    private function sanitizeOriginalFilename(string $rawName, string $extension): string
    {
        $baseName = (string) pathinfo($rawName, PATHINFO_FILENAME);
        $asciiBase = Str::ascii($baseName);
        $cleanBase = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $asciiBase) ?? '';
        $cleanBase = preg_replace('/\.{2,}/', '.', $cleanBase) ?? '';
        $cleanBase = trim($cleanBase, '.-_');

        if ($cleanBase === '') {
            $cleanBase = 'paquete-descarga';
        }

        return mb_substr($cleanBase, 0, 180) . '.' . $extension;
    }
}
