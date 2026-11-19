<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

class MediaCleanupService
{
    /**
     * Eliminar un archivo de imagen de disco y sus versiones en caché si no está siendo usado.
     */
    public static function eliminarArchivoSiHuérfano(?string $pathOrUrl): void
    {
        if (empty($pathOrUrl)) {
            return;
        }

        $filename = basename(parse_url($pathOrUrl, PHP_URL_PATH));
        if (empty($filename)) {
            return;
        }

        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        // Comprobar si el archivo está siendo usado por algún artículo activo o en papelera
        $usadoEnArticulos = Article::where('featured_image', 'like', '%' . $filename . '%')
            ->orWhere('body', 'like', '%' . $filename . '%')
            ->exists();

        if ($usadoEnArticulos) {
            return;
        }

        // Comprobar si el archivo está siendo usado por alguna página
        $usadoEnPaginas = Page::where('seo_og_image', 'like', '%' . $filename . '%')
            ->orWhere('body', 'like', '%' . $filename . '%')
            ->orWhere('layout_json', 'like', '%' . $filename . '%')
            ->exists();

        if ($usadoEnPaginas) {
            return;
        }

        // Comprobar si el archivo está siendo usado por alguna categoría
        $usadoEnCategorias = Category::where('featured_image', 'like', '%' . $filename . '%')
            ->exists();

        if ($usadoEnCategorias) {
            return;
        }

        // Comprobar si está en configuración del sitio (logo, favicon)
        $usadoEnSettings = SiteSetting::where('logo_path', 'like', '%' . $filename . '%')
            ->orWhere('favicon_path', 'like', '%' . $filename . '%')
            ->orWhere('apple_touch_icon_path', 'like', '%' . $filename . '%')
            ->orWhere('seo_default_og_image', 'like', '%' . $filename . '%')
            ->exists();

        if ($usadoEnSettings) {
            return;
        }

        // 1. Eliminar archivo original de media/
        if (Storage::disk('public')->exists('media/' . $filename)) {
            Storage::disk('public')->delete('media/' . $filename);
        }

        // 1b. Eliminar registro de la tabla media_files si existe
        MediaFile::where('filename', $filename)->delete();

        // 2. Purgar archivos de caché asociados en cache/media/
        $cacheFiles = Storage::disk('public')->files('cache/media');
        foreach ($cacheFiles as $cacheFile) {
            if (str_contains($cacheFile, $nameWithoutExt)) {
                Storage::disk('public')->delete($cacheFile);
            }
        }
    }

    /**
     * Extraer URLs de imágenes de un bloque HTML (ej: body o content).
     */
    public static function extraerUrlsImagenes(?string $html): array
    {
        if (empty($html)) {
            return [];
        }

        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Limpiar todas las imágenes huérfanas actualmente almacenadas en storage/app/public/media.
     */
    public static function limpiarTodosLosHuerfanos(): int
    {
        $archivosMedia = Storage::disk('public')->files('media');
        $eliminados = 0;

        foreach ($archivosMedia as $archivo) {
            $filename = basename($archivo);
            $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

            // Ignorar archivos del sistema (.gitignore, etc)
            if (str_starts_with($filename, '.')) {
                continue;
            }

            $usadoEnArticulos = Article::where('featured_image', 'like', '%' . $filename . '%')
                ->orWhere('body', 'like', '%' . $filename . '%')
                ->exists();

            $usadoEnPaginas = Page::where('seo_og_image', 'like', '%' . $filename . '%')
                ->orWhere('body', 'like', '%' . $filename . '%')
                ->orWhere('layout_json', 'like', '%' . $filename . '%')
                ->exists();

            $usadoEnCategorias = Category::where('featured_image', 'like', '%' . $filename . '%')
                ->exists();

            $usadoEnSettings = SiteSetting::where('logo_path', 'like', '%' . $filename . '%')
                ->orWhere('favicon_path', 'like', '%' . $filename . '%')
            ->orWhere('apple_touch_icon_path', 'like', '%' . $filename . '%')
                ->orWhere('seo_default_og_image', 'like', '%' . $filename . '%')
                ->exists();

            if (!$usadoEnArticulos && !$usadoEnPaginas && !$usadoEnCategorias && !$usadoEnSettings) {
                Storage::disk('public')->delete($archivo);
                $eliminados++;

                // Eliminar registro de la tabla media_files si existe
                MediaFile::where('filename', $filename)->delete();

                // Eliminar también caché asociada
                $cacheFiles = Storage::disk('public')->files('cache/media');
                foreach ($cacheFiles as $cacheFile) {
                    if (str_contains($cacheFile, $nameWithoutExt)) {
                        Storage::disk('public')->delete($cacheFile);
                    }
                }
            }
        }

        return $eliminados;
    }
}
