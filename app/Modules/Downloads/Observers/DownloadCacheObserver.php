<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Observers;

use App\Modules\Downloads\Models\DownloadSetting;
use App\Services\CacheInvalidationService;
use App\Services\SeoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Observador autónomo del módulo de Descargas.
 *
 * Invalida la caché pública en Redis, limpia la configuración cacheada del módulo
 * y actualiza el archivo `sitemap.xml` tras confirmar la transacción en base de datos.
 */
class DownloadCacheObserver
{
    public function saved(Model $model): void
    {
        $this->sincronizarCacheYSitemap($model);
    }

    public function deleted(Model $model): void
    {
        $this->sincronizarCacheYSitemap($model);
    }

    public function restored(Model $model): void
    {
        $this->sincronizarCacheYSitemap($model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->sincronizarCacheYSitemap($model);
    }

    private function sincronizarCacheYSitemap(Model $model): void
    {
        if ($model instanceof DownloadSetting) {
            DownloadSetting::clearCache();
            try {
                app(\App\Services\MenuService::class)->invalidarCache();
            } catch (\Throwable) {}
        }

        CacheInvalidationService::limpiarCachePublica();

        DB::afterCommit(function () {
            try {
                app(SeoService::class)->generarSitemap();
            } catch (\Throwable $e) {
                Log::warning('Error al regenerar sitemap en DownloadCacheObserver: ' . $e->getMessage());
            }
        });
    }
}
