<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Servicio profesional para la optimización y compresión de imágenes (Lighthouse Performance).
 * Convierte automáticamente imágenes JPG/PNG a WebP redimensionadas a máx 1600px.
 */
class ImageOptimizerService
{
    /**
     * Optimiza y convierte una imagen subida a formato WebP comprimido.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param int $maxWidth
     * @param int $quality
     * @return string Ruta relativa del archivo guardado en storage público.
     */
    public static function compressAndSave(UploadedFile $file, string $directory = 'media', int $maxWidth = 1600, int $quality = 82): string
    {
        $mime = strtolower($file->getMimeType() ?: '');
        $originalExt = strtolower($file->getClientOriginalExtension() ?: 'png');

        // Si es SVG o GIF, guardar directamente sin alterar
        if ($originalExt === 'svg' || $originalExt === 'gif' || str_contains($mime, 'svg') || str_contains($mime, 'gif')) {
            $filename = time() . '_' . uniqid() . '.' . $originalExt;
            return $file->storeAs($directory, $filename, 'public');
        }

        // Si GD o imagewebp no están disponibles, fallback a guardado estándar
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            $filename = time() . '_' . uniqid() . '.' . $originalExt;
            return $file->storeAs($directory, $filename, 'public');
        }

        try {
            $filePath = $file->getRealPath();
            $srcImage = match (true) {
                str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') || in_array($originalExt, ['jpg', 'jpeg']) => @imagecreatefromjpeg($filePath),
                str_contains($mime, 'png') || $originalExt === 'png' => @imagecreatefrompng($filePath),
                str_contains($mime, 'webp') || $originalExt === 'webp' => @imagecreatefromwebp($filePath),
                default => null,
            };

            if (!$srcImage) {
                $filename = time() . '_' . uniqid() . '.' . $originalExt;
                return $file->storeAs($directory, $filename, 'public');
            }

            // Preservar transparencia para PNG/WebP
            imagealphablending($srcImage, true);
            imagesavealpha($srcImage, true);

            $width = imagesx($srcImage);
            $height = imagesy($srcImage);

            // Redimensionar proporcionalmente si excede el ancho máximo
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int) round(($height / $width) * $maxWidth);
                $dstImage = imagecreatetruecolor($newWidth, $newHeight);

                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
                $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
                imagefill($dstImage, 0, 0, $transparent);

                imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($srcImage);
                $srcImage = $dstImage;
            }

            $filename = time() . '_' . uniqid() . '.webp';
            $relativeStoragePath = $directory . '/' . $filename;
            $absolutePath = storage_path('app/public/' . $relativeStoragePath);

            $dirPath = dirname($absolutePath);
            if (!file_exists($dirPath)) {
                mkdir($dirPath, 0755, true);
            }

            imagewebp($srcImage, $absolutePath, $quality);
            imagedestroy($srcImage);

            return $relativeStoragePath;
        } catch (\Throwable $e) {
            Log::warning('Fallback en ImageOptimizerService: ' . $e->getMessage());
            $filename = time() . '_' . uniqid() . '.' . $originalExt;
            return $file->storeAs($directory, $filename, 'public');
        }
    }

    /**
     * Optimiza un archivo existente en el sistema de archivos de storage.
     */
    public static function optimizeExistingFile(string $absolutePath, int $maxWidth = 1600, int $quality = 82): ?string
    {
        if (!file_exists($absolutePath) || !extension_loaded('gd') || !function_exists('imagewebp')) {
            return null;
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            return null;
        }

        try {
            $srcImage = match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($absolutePath),
                'png'         => @imagecreatefrompng($absolutePath),
                default       => null,
            };

            if (!$srcImage) {
                return null;
            }

            $width = imagesx($srcImage);
            $height = imagesy($srcImage);

            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int) round(($height / $width) * $maxWidth);
                $dstImage = imagecreatetruecolor($newWidth, $newHeight);

                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
                $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
                imagefill($dstImage, 0, 0, $transparent);

                imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($srcImage);
                $srcImage = $dstImage;
            }

            $newPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $absolutePath);
            imagewebp($srcImage, $newPath, $quality);
            imagedestroy($srcImage);

            // Eliminar archivo original no optimizado si el WebP se creó con éxito
            if (file_exists($newPath) && filesize($newPath) > 0) {
                if ($newPath !== $absolutePath) {
                    @unlink($absolutePath);
                }
                return $newPath;
            }
        } catch (\Throwable $e) {
            Log::warning('Error optimizando archivo existente: ' . $e->getMessage());
        }

        return null;
    }
}
