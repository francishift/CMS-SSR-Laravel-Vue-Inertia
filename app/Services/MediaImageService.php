<?php

namespace App\Services;

use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Servicio profesional modular de tratamiento y optimización de imágenes.
 *
 * Características principales:
 *  - Guarda la imagen/vídeo original maestro en alta resolución.
 *  - Genera nombres de archivo SEO-friendly a partir del nombre original.
 *  - Registra cada archivo en la tabla media_files de la Biblioteca de Medios.
 *  - Genera variantes redimensionadas y adaptadas bajo demanda (On-Demand Caching).
 *  - Soporta formatos modernos (WebP, AVIF, JPG, PNG) y vídeos (MP4, WebM, MOV).
 *  - Soporta modos de ajuste: 'cover' (recorte exacto), 'contain' (dentro del área), 'scale' (proporcional).
 *  - Garantiza máxima fidelidad visual mediante muestreo de alta calidad y preservación de canal alfa.
 *  - Almacena las variantes en storage/app/public/cache/media/... para entrega inmediata por Nginx/disco.
 */
class MediaImageService
{
    /**
     * Tipos MIME clasificados como imagen.
     */
    private const IMAGE_MIMES = [
        'image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/avif',
        'image/gif', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon',
        'image/ico', 'text/xml', 'application/xml', 'text/plain',
    ];

    /**
     * Tipos MIME clasificados como vídeo.
     */
    private const VIDEO_MIMES = [
        'video/mp4', 'video/webm', 'video/quicktime',
    ];

    /**
     * Guarda el archivo original subido en disco público y lo registra en la Biblioteca de Medios.
     *
     * @param UploadedFile $file       Archivo subido
     * @param string       $directory  Directorio destino dentro de storage/app/public/
     * @param string|null  $collection Colección/etiqueta opcional para organización
     * @return MediaFile|null Modelo del archivo registrado, o null si falló
     */
    public function storeMaster(UploadedFile $file, string $directory = 'media', ?string $collection = null): ?MediaFile
    {
        $mime = strtolower($file->getMimeType() ?: '');
        $originalExt = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        // Normalizar extensión según MIME o extensión original
        $ext = $this->normalizarExtension($mime, $originalExt);

        // Determinar tipo principal (imagen o vídeo)
        $type = $this->detectarTipo($mime, $ext);

        // Generar nombre SEO-friendly a partir del nombre original
        $originalFilename = $file->getClientOriginalName();
        $filename = $this->generarNombreSeo($originalFilename, $ext, $directory);

        // Guardar archivo en disco público
        $path = $file->storeAs($directory, $filename, 'public');

        if (!$path) {
            return null;
        }

        // Obtener dimensiones para imágenes
        $width = null;
        $height = null;
        if ($type === 'image' && !in_array($ext, ['svg', 'ico'])) {
            $absolutePath = storage_path('app/public/' . $path);
            $dimensions = @getimagesize($absolutePath);
            if ($dimensions) {
                $width = $dimensions[0];
                $height = $dimensions[1];
            }
        }

        // Optimizar vídeo con FFmpeg +faststart si es vídeo y FFmpeg está instalado
        if ($type === 'video') {
            $absolutePath = storage_path('app/public/' . $path);
            $this->optimizarVideoFaststart($absolutePath);
        }

        // Registrar en la base de datos
        $mediaFile = MediaFile::create([
            'filename'          => $filename,
            'original_filename' => $originalFilename,
            'disk'              => 'public',
            'path'              => $path,
            'mime_type'         => $mime,
            'type'              => $type,
            'extension'         => $ext,
            'size'              => file_exists(storage_path('app/public/' . $path)) ? filesize(storage_path('app/public/' . $path)) : $file->getSize(),
            'width'             => $width,
            'height'            => $height,
            'collection'        => $collection,
            'uploaded_by'       => Auth::id() ?? 1,
        ]);

        return $mediaFile;
    }

    /**
     * Comprobar si la herramienta FFmpeg está disponible en el servidor.
     */
    public function hasFFmpeg(): bool
    {
        $exec = @shell_exec('which ffmpeg 2>&1');
        return !empty($exec) && !str_contains($exec, 'not found');
    }

    /**
     * Optimizar vídeo MP4 mediante FFmpeg desplazando el atom 'moov' al inicio (+faststart).
     * Ejecución sin pérdidas (-c copy).
     */
    public function optimizarVideoFaststart(string $absolutePath): bool
    {
        if (!file_exists($absolutePath) || !$this->hasFFmpeg()) {
            return false;
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp4', 'mov'])) {
            return false;
        }

        $tempOptimizedPath = $absolutePath . '.faststart.mp4';
        $cmd = sprintf(
            'ffmpeg -y -i %s -c copy -movflags +faststart %s 2>&1',
            escapeshellarg($absolutePath),
            escapeshellarg($tempOptimizedPath)
        );

        @exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($tempOptimizedPath) && filesize($tempOptimizedPath) > 0) {
            @rename($tempOptimizedPath, $absolutePath);
            Log::info("Vídeo optimizado exitosamente con FFmpeg +faststart: {$absolutePath}");
            return true;
        }

        if (file_exists($tempOptimizedPath)) {
            @unlink($tempOptimizedPath);
        }

        Log::warning("No se pudo optimizar el vídeo con FFmpeg (Código {$returnCode}): " . implode("\n", $output));
        return false;
    }

    /**
     * Generar un nombre de archivo SEO-friendly normalizado.
     * Si ya existe un archivo con ese nombre, añade sufijo incremental (-2, -3, etc.).
     */
    private function generarNombreSeo(string $originalName, string $ext, string $directory): string
    {
        // Extraer nombre sin extensión y normalizar con Str::slug
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $slug = Str::slug($baseName, '-');

        // Si el slug queda vacío (caracteres raros), usar fallback
        if (empty($slug)) {
            $slug = 'archivo-' . time();
        }

        $filename = $slug . '.' . $ext;

        // Resolver colisiones con sufijo incremental
        $counter = 1;
        while (Storage::disk('public')->exists($directory . '/' . $filename)) {
            $counter++;
            $filename = $slug . '-' . $counter . '.' . $ext;
        }

        return $filename;
    }

    /**
     * Normalizar la extensión del archivo según su MIME type y extensión original.
     */
    private function normalizarExtension(string $mime, string $originalExt): string
    {
        return match (true) {
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') || in_array($originalExt, ['jpg', 'jpeg']) => 'jpg',
            str_contains($mime, 'png') || $originalExt === 'png' => 'png',
            str_contains($mime, 'webp') || $originalExt === 'webp' => 'webp',
            str_contains($mime, 'avif') || $originalExt === 'avif' => 'avif',
            str_contains($mime, 'svg') || $originalExt === 'svg' => 'svg',
            str_contains($mime, 'gif') || $originalExt === 'gif' => 'gif',
            str_contains($mime, 'icon') || str_contains($mime, 'ico') || $originalExt === 'ico' => 'ico',
            str_contains($mime, 'mp4') || $originalExt === 'mp4' => 'mp4',
            str_contains($mime, 'webm') || $originalExt === 'webm' => 'webm',
            str_contains($mime, 'quicktime') || $originalExt === 'mov' => 'mov',
            default => $originalExt ?: 'jpg',
        };
    }

    /**
     * Detectar si el archivo es imagen o vídeo según su MIME type.
     */
    private function detectarTipo(string $mime, string $ext): string
    {
        if (in_array($ext, ['mp4', 'webm', 'mov']) || in_array($mime, self::VIDEO_MIMES)) {
            return 'video';
        }
        return 'image';
    }

    /**
     * Obtiene o genera bajo demanda la ruta absoluta en disco de una variante optimizada.
     *
     * @param string $relativePath Ruta relativa en storage/app/public (ej: "media/123.jpg")
     * @param array $options Opciones: w (ancho), h (alto), fit ('cover','contain','scale'), q (calidad), f (formato)
     * @return array Array con ['path' => ruta_absoluta, 'mime' => tipo_mime, 'hit' => bool]
     */
    public function getOrGenerateVariant(string $relativePath, array $options = []): ?array
    {
        // Limpiar y normalizar la ruta relativa
        $cleanPath = ltrim(str_replace(['/storage/', 'storage/'], '', $relativePath), '/');
        $absoluteSourcePath = storage_path('app/public/' . $cleanPath);

        if (!file_exists($absoluteSourcePath)) {
            return null;
        }

        // Si es SVG, GIF animado o ICO, retornar el archivo original directamente
        $ext = strtolower(pathinfo($absoluteSourcePath, PATHINFO_EXTENSION));
        if ($ext === 'svg' || $ext === 'gif' || $ext === 'ico') {
            $mime = match ($ext) {
                'svg' => 'image/svg+xml',
                'gif' => 'image/gif',
                'ico' => 'image/x-icon',
                default => 'image/x-icon',
            };
            return [
                'path' => $absoluteSourcePath,
                'mime' => $mime,
                'hit'  => true,
            ];
        }

        // Opciones por defecto: WebP al 90% de calidad (alta definición sin artefactos)
        $width   = isset($options['w']) ? (int) $options['w'] : null;
        $height  = isset($options['h']) ? (int) $options['h'] : null;
        $fit     = in_array($options['fit'] ?? '', ['cover', 'contain', 'scale']) ? $options['fit'] : 'scale';
        $quality = isset($options['q']) ? max(10, min(100, (int) $options['q'])) : 90;
        $format  = strtolower($options['f'] ?? 'webp');

        if (!in_array($format, ['webp', 'jpg', 'jpeg', 'png', 'avif'])) {
            $format = 'webp';
        }

        // Si no se especifican dimensiones ni cambio de formato/calidad, devolver el original
        if (!$width && !$height && $format === $ext && $quality === 100) {
            return [
                'path' => $absoluteSourcePath,
                'mime' => $this->getMimeForFormat($ext),
                'hit'  => true,
            ];
        }

        // Crear nombre único para la variante en el directorio de caché
        $hashKey = md5("{$cleanPath}_w{$width}_h{$height}_fit{$fit}_q{$quality}_f{$format}");
        $cacheFilename = "cache/media/{$hashKey}_" . pathinfo($cleanPath, PATHINFO_FILENAME) . '.' . $format;
        $cacheAbsolutePath = storage_path('app/public/' . $cacheFilename);

        // Si la variante ya fue generada previamente, entregar directamente (CACHE HIT - 0ms)
        if (file_exists($cacheAbsolutePath) && filesize($cacheAbsolutePath) > 0) {
            return [
                'path' => $cacheAbsolutePath,
                'mime' => $this->getMimeForFormat($format),
                'hit'  => true,
            ];
        }

        // Generar la variante en disco (CACHE MISS)
        $success = $this->renderAndSaveImage($absoluteSourcePath, $cacheAbsolutePath, $width, $height, $fit, $quality, $format);

        if (!$success || !file_exists($cacheAbsolutePath)) {
            return [
                'path' => $absoluteSourcePath,
                'mime' => $this->getMimeForFormat($ext),
                'hit'  => false,
            ];
        }

        return [
            'path' => $cacheAbsolutePath,
            'mime' => $this->getMimeForFormat($format),
            'hit'  => false,
        ];
    }

    /**
     * Pre-genera las variantes más comunes tras la subida para que cuando
     * los usuarios públicos visiten la web la caché esté 100% caliente (0ms).
     *
     * Las variantes coinciden exactamente con los parámetros que piden los
     * componentes Vue públicos (ResponsiveImage, imageUrl) para garantizar
     * CACHE HIT en la primera visita de cada usuario.
     */
    public function prewarmVariants(string $relativePath): void
    {
        try {
            // Variantes que generan los componentes públicos:
            $variants = [
                // Srcset de grids (ElementGridArticles, CategoryTemplateGrid, ArticleCard)
                // widths: [320, 480, 640] con fit=cover y proporciones 16:9
                ['w' => 320, 'h' => 180, 'fit' => 'cover', 'q' => 85, 'f' => 'webp'],
                ['w' => 480, 'h' => 270, 'fit' => 'cover', 'q' => 85, 'f' => 'webp'],
                ['w' => 640, 'h' => 360, 'fit' => 'cover', 'q' => 85, 'f' => 'webp'],
                // Article.vue detalle (ResponsiveImage default widths)
                ['w' => 1600, 'fit' => 'scale', 'q' => 90, 'f' => 'webp'],
                // BuilderSection / BuilderColumn fondo responsivo (Móvil, Tablet, PC)
                ['w' => 768, 'fit' => 'scale', 'q' => 82, 'f' => 'webp'],
                ['w' => 1280, 'fit' => 'scale', 'q' => 85, 'f' => 'webp'],
                ['w' => 1920, 'fit' => 'scale', 'q' => 85, 'f' => 'webp'],
            ];

            foreach ($variants as $opts) {
                $this->getOrGenerateVariant($relativePath, $opts);
            }
        } catch (\Throwable $e) {
            Log::warning('Error en prewarmVariants: ' . $e->getMessage());
        }
    }

    /**
     * Elimina todas las variantes cacheadas de una imagen dada.
     */
    public function purgeVariants(string $relativePath): void
    {
        try {
            $filename = pathinfo($relativePath, PATHINFO_FILENAME);
            $cacheDir = storage_path('app/public/cache/media');

            if (file_exists($cacheDir)) {
                $files = glob("{$cacheDir}/*_{$filename}.*");
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Error al purgar variantes de imagen: ' . $e->getMessage());
        }
    }

    /**
     * Renderiza la imagen utilizando GD / Imagick con alta fidelidad y la guarda en caché.
     */
    private function renderAndSaveImage(
        string $sourcePath,
        string $targetPath,
        ?int $targetW,
        ?int $targetH,
        string $fit,
        int $quality,
        string $format
    ): bool {
        if (!extension_loaded('gd')) {
            return false;
        }

        try {
            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
            $srcImage = match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
                'png'         => @imagecreatefrompng($sourcePath),
                'webp'        => @imagecreatefromwebp($sourcePath),
                default       => null,
            };

            if (!$srcImage) {
                return false;
            }

            $origW = imagesx($srcImage);
            $origH = imagesy($srcImage);

            if ($origW <= 0 || $origH <= 0) {
                imagedestroy($srcImage);
                return false;
            }

            // Calcular nuevas dimensiones y coordenadas de corte
            [$dstW, $dstH, $srcX, $srcY, $srcW, $srcH] = $this->calculateCropCoordinates($origW, $origH, $targetW, $targetH, $fit);

            // Crear lienzo de destino con soporte transparente
            $dstImage = imagecreatetruecolor($dstW, $dstH);
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);

            $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
            imagefill($dstImage, 0, 0, $transparent);

            // Muestreo de alta calidad Lanczos/Bicúbico
            imagecopyresampled($dstImage, $srcImage, 0, 0, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
            imagedestroy($srcImage);

            // Crear directorio de caché si no existe
            $dir = dirname($targetPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            // Guardar en el formato destino solicitado
            $saved = match ($format) {
                'webp' => function_exists('imagewebp') && imagewebp($dstImage, $targetPath, $quality),
                'avif' => function_exists('imageavif') && imageavif($dstImage, $targetPath, $quality),
                'png'  => imagepng($dstImage, $targetPath, (int) round((100 - $quality) / 10)),
                'jpg', 'jpeg' => imagejpeg($dstImage, $targetPath, $quality),
                default => false,
            };

            // Fallback a WebP o JPG si AVIF no está soportado en GD
            if (!$saved && $format === 'avif' && function_exists('imagewebp')) {
                $targetPath = preg_replace('/\.avif$/i', '.webp', $targetPath);
                $saved = imagewebp($dstImage, $targetPath, $quality);
            }

            imagedestroy($dstImage);

            return (bool) $saved;
        } catch (\Throwable $e) {
            Log::warning("Error procesando imagen para {$targetPath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcula las coordenadas de escalado y corte (cover, contain, scale).
     */
    private function calculateCropCoordinates(int $origW, int $origH, ?int $targetW, ?int $targetH, string $fit): array
    {
        // Si no hay límites, mantener tamaño original
        if (!$targetW && !$targetH) {
            return [$origW, $origH, 0, 0, $origW, $origH];
        }

        // Si solo hay un eje, escalar proporcionalmente
        if ($targetW && !$targetH) {
            $dstW = min($origW, $targetW);
            $dstH = (int) round(($origH / $origW) * $dstW);
            return [$dstW, $dstH, 0, 0, $origW, $origH];
        }

        if (!$targetW && $targetH) {
            $dstH = min($origH, $targetH);
            $dstW = (int) round(($origW / $origH) * $dstH);
            return [$dstW, $dstH, 0, 0, $origW, $origH];
        }

        // Si hay ancho y alto especificados:
        if ($fit === 'cover') {
            // Recorte exacto para llenar el recuadro (Center Crop)
            $ratioW = $origW / $targetW;
            $ratioH = $origH / $targetH;
            $scale  = min($ratioW, $ratioH);

            $srcW = (int) round($targetW * $scale);
            $srcH = (int) round($targetH * $scale);

            $srcX = (int) round(($origW - $srcW) / 2);
            $srcY = (int) round(($origH - $srcH) / 2);

            return [$targetW, $targetH, $srcX, $srcY, $srcW, $srcH];
        }

        if ($fit === 'contain') {
            // Ajustar dentro del cuadro sin cortar nada
            $scale = min($targetW / $origW, $targetH / $origH);
            $dstW = (int) round($origW * $scale);
            $dstH = (int) round($origH * $scale);

            return [$dstW, $dstH, 0, 0, $origW, $origH];
        }

        // 'scale' o por defecto: escalar proporcionalmente sin sobrepasar
        $scale = min($targetW / $origW, $targetH / $origH);
        if ($scale >= 1) {
            return [$origW, $origH, 0, 0, $origW, $origH];
        }

        $dstW = (int) round($origW * $scale);
        $dstH = (int) round($origH * $scale);

        return [$dstW, $dstH, 0, 0, $origW, $origH];
    }

    private function getMimeForFormat(string $format): string
    {
        return match ($format) {
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'png'  => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            default => 'image/jpeg',
        };
    }
}
