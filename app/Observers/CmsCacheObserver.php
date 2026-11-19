<?php

namespace App\Observers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Services\CacheInvalidationService;
use App\Services\SeoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Observador de modelos Eloquent para la invalidación automática de caché en Redis
 * y la regeneración instantánea del sitemap.xml.
 *
 * Escucha los eventos de guardado, actualización, eliminación y restauración
 * de modelos para mantener las páginas públicas del sitio y el índice SEO siempre al día.
 */
class CmsCacheObserver
{
    public function saved(Model $model): void
    {
        CacheInvalidationService::limpiarCachePublica();
        $this->regenerarSitemapSiAplica($model);
    }

    public function deleted(Model $model): void
    {
        CacheInvalidationService::limpiarCachePublica();
        $this->regenerarSitemapSiAplica($model);
    }

    public function restored(Model $model): void
    {
        CacheInvalidationService::limpiarCachePublica();
        $this->regenerarSitemapSiAplica($model);
    }

    public function forceDeleted(Model $model): void
    {
        CacheInvalidationService::limpiarCachePublica();
        $this->regenerarSitemapSiAplica($model);
    }

    /**
     * Regenera el archivo sitemap.xml si el modelo modificado afecta al contenido público indexable.
     */
    private function regenerarSitemapSiAplica(Model $model): void
    {
        if ($model instanceof Article || $model instanceof Category || $model instanceof Page) {
            DB::afterCommit(function () {
                try {
                    app(SeoService::class)->generarSitemap();
                } catch (\Throwable $e) {
                    Log::warning('Error al regenerar sitemap en CmsCacheObserver: ' . $e->getMessage());
                }
            });
        }
    }
}
