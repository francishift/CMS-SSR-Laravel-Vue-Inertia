<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Providers;

use App\Modules\Downloads\Contracts\DownloadAccessStrategyInterface;
use App\Modules\Downloads\Contracts\DownloadSlugHandlerInterface;
use App\Modules\Downloads\Http\Controllers\PublicDownloadSlugHandler;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use App\Modules\Downloads\Observers\DownloadCacheObserver;
use App\Modules\Downloads\Services\DownloadSeoBridgeService;
use App\Modules\Downloads\Strategies\StandardDownloadAccessStrategy;
use App\Services\SeoService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider autónomo del Módulo de Descargas.
 *
 * Para instalar este módulo en otro portal CMS, basta con copiar `app/Modules/Downloads`,
 * las vistas `Pages/Descargas` + `Pages/Public/Downloads` + `DownloadCard.vue`, y registrar
 * este proveedor en `bootstrap/providers.php`.
 */
class DownloadsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/downloads.php', 'downloads');

        // Registrar dinámicamente el disco privado de la bóveda si el proyecto anfitrión no lo definió en filesystems.php
        $vaultDisk = (string) config('downloads.vault.disk', 'downloads_vault');
        if (!config()->has("filesystems.disks.{$vaultDisk}")) {
            config()->set("filesystems.disks.{$vaultDisk}", [
                'driver'     => 'local',
                'root'       => (string) config('downloads.vault.root', storage_path('app/private/downloads')),
                'visibility' => 'private',
                'serve'      => false,
                'throw'      => false,
                'report'     => false,
            ]);
        }

        // Contratos desacoplados (Inversión de Dependencias — SOLID)
        $this->app->bind(DownloadAccessStrategyInterface::class, StandardDownloadAccessStrategy::class);
        $this->app->bind(DownloadSlugHandlerInterface::class, PublicDownloadSlugHandler::class);
    }

    public function boot(): void
    {
        // Cargar migraciones autónomas del módulo sin ensuciar database/migrations global
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Config/downloads.php' => config_path('downloads.php'),
            ], 'downloads-config');
        }

        // Observadores para invalidación instantánea de Redis y regeneración de sitemap
        DownloadItem::observe(DownloadCacheObserver::class);
        DownloadSetting::observe(DownloadCacheObserver::class);

        // Conectar extensor de Sitemap de forma desacoplada
        SeoService::registerSitemapExtender([DownloadSeoBridgeService::class, 'appendSitemapUrls']);
    }
}
