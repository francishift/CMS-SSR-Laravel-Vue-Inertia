<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de configuración SEO dinámica.
 *
 * Configura los defaults de la librería seotools con los valores
 * almacenados en la tabla site_settings (caché de Redis), en vez
 * de usar las variables de entorno estáticas de seotools.php.
 *
 * Esto garantiza que title, description y og:site_name
 * reflejen siempre los valores editados desde el panel de admin.
 */
class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Solo configurar en peticiones web (no en artisan, queue, etc.)
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            $settings = SiteSetting::cached();

            $siteName = $settings->site_name ?? config('app.name', 'CMS SSR');
            $defaultDescription = $settings->seo_default_description ?? '';
            $defaultOgImage = $settings->seo_default_og_image ?? '';

            config([
                // Meta defaults dinámicos
                'seotools.meta.defaults.title'       => $siteName,
                'seotools.meta.defaults.description'  => $defaultDescription,

                // Open Graph defaults dinámicos
                'seotools.opengraph.defaults.title'       => $siteName,
                'seotools.opengraph.defaults.description'  => $defaultDescription,
                'seotools.opengraph.defaults.site_name'    => $siteName,
                'seotools.opengraph.defaults.images'       => $defaultOgImage ? [url($defaultOgImage)] : [],

                // JSON-LD defaults dinámicos
                'seotools.json-ld.defaults.title'       => $siteName,
                'seotools.json-ld.defaults.description'  => $defaultDescription,
                'seotools.json-ld.defaults.images'       => $defaultOgImage ? [url($defaultOgImage)] : [],
            ]);
        } catch (\Throwable) {
            // Si la BD no está disponible (ej: durante migraciones), no hacer nada
        }
    }
}
