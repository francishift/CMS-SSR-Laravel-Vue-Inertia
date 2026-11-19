<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Comando para migrar archivos multimedia existentes a la tabla media_files.
 *
 * Escanea storage/app/public/media/ y registra cada archivo que no tenga
 * ya un registro en la base de datos. Es idempotente: se puede ejecutar
 * múltiples veces sin crear duplicados.
 */
class MigrateExistingMedia extends Command
{
    protected $signature = 'media:migrate-existing
                            {--dry-run : Simular sin escribir en BBDD}';

    protected $description = 'Registrar archivos multimedia existentes en disco en la tabla media_files de la Biblioteca de Medios';

    /**
     * Extensiones de imagen soportadas.
     */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif', 'svg', 'ico'];

    /**
     * Extensiones de vídeo soportadas.
     */
    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov'];

    public function handle(): int
    {
        $this->info('🔍 Escaneando archivos en storage/app/public/media/ ...');

        $archivos = Storage::disk('public')->files('media');
        $total = count($archivos);
        $registrados = 0;
        $omitidos = 0;

        if ($total === 0) {
            $this->warn('No se encontraron archivos en la carpeta media/.');
            return self::SUCCESS;
        }

        $this->info("📂 Se encontraron {$total} archivos. Procesando...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($archivos as $archivo) {
            $filename = basename($archivo);

            // Ignorar archivos del sistema
            if (str_starts_with($filename, '.')) {
                $bar->advance();
                continue;
            }

            // Verificar si ya existe en BBDD (idempotencia)
            if (MediaFile::where('path', $archivo)->exists()) {
                $omitidos++;
                $bar->advance();
                continue;
            }

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Determinar tipo
            $type = 'image';
            if (in_array($ext, self::VIDEO_EXTENSIONS)) {
                $type = 'video';
            } elseif (!in_array($ext, self::IMAGE_EXTENSIONS)) {
                $bar->advance();
                continue;
            }

            // Obtener datos del archivo
            $absolutePath = storage_path('app/public/' . $archivo);
            $size = @filesize($absolutePath) ?: 0;
            $mimeType = @mime_content_type($absolutePath) ?: 'application/octet-stream';

            // Obtener dimensiones para imágenes
            $width = null;
            $height = null;
            if ($type === 'image' && !in_array($ext, ['svg', 'ico'])) {
                $dimensions = @getimagesize($absolutePath);
                if ($dimensions) {
                    $width = $dimensions[0];
                    $height = $dimensions[1];
                }
            }

            if ($this->option('dry-run')) {
                $registrados++;
                $bar->advance();
                continue;
            }

            // Crear registro en BBDD
            MediaFile::create([
                'uuid'              => (string) Str::uuid(),
                'filename'          => $filename,
                'original_filename' => $filename,
                'disk'              => 'public',
                'path'              => $archivo,
                'mime_type'         => $mimeType,
                'type'              => $type,
                'extension'         => $ext,
                'size'              => $size,
                'width'             => $width,
                'height'            => $height,
                'collection'        => null,
                'uploaded_by'       => User::first()?->id ?? 1, // Primer usuario existente
            ]);

            $registrados++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $prefijo = $this->option('dry-run') ? '[DRY-RUN] ' : '';
        $this->info("{$prefijo}✅ Proceso completado:");
        $this->info("   📄 Total archivos encontrados: {$total}");
        $this->info("   ✅ Registrados en BBDD: {$registrados}");
        $this->info("   ⏭️  Omitidos (ya existían): {$omitidos}");

        return self::SUCCESS;
    }
}
