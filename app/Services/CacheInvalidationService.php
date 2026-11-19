<?php

namespace App\Services;

use App\Http\Middleware\PublicResponseCache;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio centralizado de invalidación de caché para el CMS.
 *
 * Se encarga de limpiar las etiquetas de Redis cuando se detecta
 * cualquier modificación de contenido en el panel de administración.
 */
class CacheInvalidationService
{
    /**
     * Limpiar toda la caché pública de respuestas en Redis y la caché de configuración.
     */
    public static function limpiarCachePublica(): void
    {
        try {
            // 1. Limpiar las respuestas HTML/JSON almacenadas en Redis por la etiqueta public_pages
            if (Cache::supportsTags()) {
                Cache::tags([
                    PublicResponseCache::CACHE_TAG,
                    'articles',
                    'categories',
                    'pages',
                    'menus',
                    'site-settings',
                ])->flush();
            }

            try {
                Cache::store('redis')->tags([PublicResponseCache::CACHE_TAG])->flush();
            } catch (\Throwable) {
                // Fallback silencioso si el almacén redis es igual al almacén por defecto
            }

            // 2. Limpiar la caché de configuración global del sitio
            SiteSetting::clearCache();
        } catch (\Throwable $e) {
            Log::warning('Error al limpiar la caché pública en Redis: ' . $e->getMessage());
        }
    }
}
