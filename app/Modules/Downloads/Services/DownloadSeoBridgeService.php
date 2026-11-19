<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Services;

use App\Models\SiteSetting;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use App\Services\SeoService;
use Artesaos\SEOTools\Facades\JsonLd;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

/**
 * Puente SEO autónomo del módulo de Descargas.
 *
 * Configura metadatos OpenGraph, Twitter Cards, directivas robots,
 * nodos Schema.org (`CollectionPage` + `ItemList` y `SoftwareApplication` + `Offer`)
 * e inyecta las URLs públicas en el `sitemap.xml` solo cuando la visibilidad es pública.
 */
class DownloadSeoBridgeService
{
    public function __construct(
        private readonly SeoService $seoService,
    ) {}

    /**
     * Configura SEO completo para la portada del catálogo de descargas.
     *
     * @param  array<int, array{name: string, url: string}>  $breadcrumb
     */
    public function configurarCatalogo(
        DownloadSetting $dlSettings,
        LengthAwarePaginator $items,
        string $siteName,
        int $pageNumber = 1,
        array $breadcrumb = []
    ): void {
        $siteSettings = SiteSetting::cached();
        $baseTitle = !empty($dlSettings->seo_title) ? $dlSettings->seo_title : $dlSettings->page_title;
        $title = $pageNumber > 1 ? "{$baseTitle} — Página {$pageNumber}" : $baseTitle;

        $description = !empty($dlSettings->seo_description)
            ? $dlSettings->seo_description
            : ($dlSettings->description ?: ($siteSettings->seo_default_description ?: $siteSettings->site_description ?: "Catálogo de {$dlSettings->page_title} en {$siteName}"));

        if ($pageNumber > 1) {
            $description = "{$description} — Página {$pageNumber}";
        }

        $canonicalUrl = $dlSettings->publicIndexUrl($pageNumber);

        SEOMeta::setTitle($title, false);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($canonicalUrl);

        // Si el catálogo aún está en modo solo backend ('admin_only'), bloquear indexación estrictamente
        if (!$dlSettings->isStrictlyPublic()) {
            SEOMeta::setRobots('noindex, nofollow');
        } elseif (!empty($dlSettings->meta_robots)) {
            SEOMeta::setRobots($dlSettings->meta_robots);
        }

        OpenGraph::setTitle("{$title} - {$siteName}");
        OpenGraph::setDescription($description);
        OpenGraph::setUrl($canonicalUrl);
        OpenGraph::addProperty('type', 'website');
        OpenGraph::addProperty('site_name', $siteName);

        $ogImage = $dlSettings->seo_og_image ?: $siteSettings->seo_default_og_image ?: $siteSettings->logo_path;
        if ($ogImage) {
            OpenGraph::addImage(url($ogImage));
            JsonLd::addImage(url($ogImage));
        }

        TwitterCard::setTitle("{$title} - {$siteName}");
        TwitterCard::setDescription($description);
        TwitterCard::setUrl($canonicalUrl);
        TwitterCard::setType('summary_large_image');
        if ($ogImage) {
            TwitterCard::setImage(url($ogImage));
        }

        $this->seoService->inicializarContextoModulo($siteName, $description, $breadcrumb, $canonicalUrl);

        $listElements = [];
        foreach ($items->items() as $idx => $item) {
            /** @var DownloadItem $item */
            $listElements[] = [
                '@type'    => 'ListItem',
                'position' => $idx + 1,
                'name'     => $item->title,
                'url'      => $dlSettings->publicItemUrl($item->slug),
            ];
        }

        $collectionNode = [
            '@type'       => 'CollectionPage',
            '@id'         => $canonicalUrl . '#downloads-collection',
            'isPartOf'    => ['@id' => url('/#website')],
            'name'        => $title,
            'description' => $description,
            'url'         => $canonicalUrl,
            'inLanguage'  => str_replace('_', '-', app()->getLocale()),
            'publisher'   => ['@id' => url('/#organization')],
        ];

        if ($ogImage) {
            $collectionNode['image'] = [url($ogImage)];
        }

        if (!empty($breadcrumb)) {
            $collectionNode['breadcrumb'] = ['@id' => $canonicalUrl . '#breadcrumb'];
        }

        if (!empty($listElements)) {
            $collectionNode['mainEntity'] = [
                '@type'           => 'ItemList',
                'numberOfItems'   => count($listElements),
                'itemListElement' => $listElements,
            ];
        }

        $this->seoService->registrarNodoGraph('downloads_collection', $collectionNode);
    }

    /**
     * Configura SEO y Schema.org `SoftwareApplication` + `Offer` para una descarga individual.
     *
     * @param  array<int, array{name: string, url: string}>  $breadcrumb
     */
    public function configurarItem(
        DownloadSetting $dlSettings,
        DownloadItem $item,
        string $siteName,
        array $breadcrumb = []
    ): void {
        $siteSettings = SiteSetting::cached();
        $title = !empty($item->seo_title) ? $item->seo_title : $item->title;
        $description = !empty($item->seo_description)
            ? $item->seo_description
            : ($item->short_description ?: '');

        if (empty($description) && !empty($item->body)) {
            $cleaned = trim(strip_tags($item->body));
            if ($cleaned !== '') {
                $description = mb_substr($cleaned, 0, 160);
            }
        }
        if (empty($description)) {
            $description = $siteSettings->seo_default_description ?: $siteSettings->site_description ?: $title;
        }

        $canonicalUrl = $dlSettings->publicItemUrl($item->slug);

        SEOMeta::setTitle($title, false);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($canonicalUrl);

        if (!$dlSettings->isStrictlyPublic() || !$item->isPublished()) {
            SEOMeta::setRobots('noindex, nofollow');
        } elseif (!empty($item->meta_robots)) {
            SEOMeta::setRobots($item->meta_robots);
        }

        OpenGraph::setTitle("{$title} - {$siteName}");
        OpenGraph::setDescription($description);
        OpenGraph::setUrl($canonicalUrl);
        OpenGraph::addProperty('type', 'website');
        OpenGraph::addProperty('site_name', $siteName);

        $schemaImage = $item->seo_og_image ?: $item->featured_image ?: $dlSettings->seo_og_image ?: $siteSettings->seo_default_og_image;

        // Las redes sociales (OpenGraph / X Cards) requieren imagen rasterizada (PNG/JPG/WebP), no SVG
        $socialOgImage = $item->seo_og_image;
        if (empty($socialOgImage) && !empty($item->featured_image) && !str_ends_with(strtolower($item->featured_image), '.svg')) {
            $socialOgImage = $item->featured_image;
        }
        $socialOgImage = $socialOgImage ?: $dlSettings->seo_og_image ?: $siteSettings->seo_default_og_image;

        if ($socialOgImage && !str_ends_with(strtolower($socialOgImage), '.svg')) {
            OpenGraph::addImage(url($socialOgImage));
            TwitterCard::setImage(url($socialOgImage));
        }

        if ($schemaImage) {
            JsonLd::addImage(url($schemaImage));
        }

        TwitterCard::setTitle("{$title} - {$siteName}");
        TwitterCard::setDescription($description);
        TwitterCard::setUrl($canonicalUrl);
        TwitterCard::setType('summary_large_image');

        $this->seoService->inicializarContextoModulo($siteName, $description, $breadcrumb, $canonicalUrl);

        $priceDecimal = $item->isPaid()
            ? number_format($item->price_cents / 100, 2, '.', '')
            : '0.00';

        $softwareNode = [
            '@type'               => 'SoftwareApplication',
            '@id'                 => $canonicalUrl . '#software',
            'name'                => $item->title,
            'description'         => $description,
            'url'                 => $canonicalUrl,
            'applicationCategory' => 'DeveloperApplication',
            'operatingSystem'     => $item->compatibility ?: 'Web / CMS',
            'author'              => ['@id' => url('/#organization')],
            'publisher'           => ['@id' => url('/#organization')],
            'isPartOf'            => ['@id' => url('/#website')],
            'inLanguage'          => str_replace('_', '-', app()->getLocale()),
            'offers'              => [
                '@type'         => 'Offer',
                'price'         => $priceDecimal,
                'priceCurrency' => strtoupper((string) ($item->currency ?: 'EUR')),
                'availability'  => 'https://schema.org/InStock',
                'url'           => $canonicalUrl,
            ],
        ];

        if (!empty($item->compatibility)) {
            $softwareNode['softwareRequirements'] = $item->compatibility;
        }

        if (!empty($item->license_type)) {
            $softwareNode['license'] = $this->resolverLicenciaSchema((string) $item->license_type);
        }

        if (!empty($item->version)) {
            $softwareNode['softwareVersion'] = $item->version;
        }

        if (!empty($item->changelog)) {
            $cleanChangelog = trim(preg_replace('/\s+/', ' ', strip_tags((string) $item->changelog)) ?? '');
            if ($cleanChangelog !== '') {
                $softwareNode['releaseNotes'] = mb_substr($cleanChangelog, 0, 500);
            }
        }

        if ($item->file_size_bytes > 0) {
            $softwareNode['fileSize'] = $item->formattedFileSize();
        }

        if ($schemaImage) {
            $softwareNode['image'] = [url($schemaImage)];
        }

        if ($item->published_at) {
            $softwareNode['datePublished'] = $item->published_at->toIso8601String();
        }

        if ($item->updated_at) {
            $softwareNode['dateModified'] = $item->updated_at->toIso8601String();
        }

        if (!empty($breadcrumb)) {
            $softwareNode['breadcrumb'] = ['@id' => $canonicalUrl . '#breadcrumb'];
        }

        $this->seoService->registrarNodoGraph('download_software', $softwareNode);
    }

    /**
     * Extiende el sitemap XML únicamente cuando el catálogo es público e indexable.
     */
    public static function appendSitemapUrls(Sitemap $sitemap): void
    {
        try {
            $settings = DownloadSetting::cached();
            if (!$settings->isStrictlyPublic()) {
                return;
            }

            $catalogHasNoIndex = !empty($settings->meta_robots) && str_contains(strtolower($settings->meta_robots), 'noindex');
            if (!$catalogHasNoIndex) {
                $catalogPath = '/' . ltrim($settings->slug, '/');
                $catalogUrlTag = Url::create($catalogPath)
                    ->setLastModificationDate($settings->updated_at ?? now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8);

                if (!empty($settings->seo_og_image)) {
                    $catalogUrlTag->addImage(url($settings->seo_og_image), $settings->page_title);
                }

                $sitemap->add($catalogUrlTag);
            }

            $items = DownloadItem::publicados()
                ->where(function ($q) {
                    $q->whereNull('meta_robots')
                      ->orWhere('meta_robots', 'not like', '%noindex%');
                })
                ->orderBy('order', 'asc')
                ->get(['slug', 'title', 'featured_image', 'seo_og_image', 'updated_at']);

            foreach ($items as $item) {
                $itemPath = '/' . ltrim($settings->slug, '/') . '/' . ltrim($item->slug, '/');
                $urlTag = Url::create($itemPath)
                    ->setLastModificationDate($item->updated_at ?? now())
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.7);

                $img = $item->seo_og_image ?: $item->featured_image;
                if ($img) {
                    $urlTag->addImage(url($img), $item->title);
                }

                $sitemap->add($urlTag);
            }
        } catch (\Throwable) {
            // Fallo silencioso si las tablas aún no están migradas durante despliegue inicial
        }
    }

    /**
     * Normaliza la licencia declarada hacia su URL canónica oficial para Schema.org `license` cuando es estándar.
     */
    private function resolverLicenciaSchema(string $licenseType): string
    {
        $trimmed = trim($licenseType);
        if (str_starts_with(strtolower($trimmed), 'http://') || str_starts_with(strtolower($trimmed), 'https://')) {
            return $trimmed;
        }

        $lower = strtolower($trimmed);
        if (str_contains($lower, 'gpl-3') || str_contains($lower, 'gpl v3') || str_contains($lower, 'gplv3')) {
            return 'https://www.gnu.org/licenses/gpl-3.0.html';
        }
        if (str_contains($lower, 'gpl-2') || str_contains($lower, 'gpl v2') || str_contains($lower, 'gplv2') || str_contains($lower, 'gnu gpl')) {
            return 'https://www.gnu.org/licenses/old-licenses/gpl-2.0.html';
        }
        if ($lower === 'mit' || str_contains($lower, 'mit license')) {
            return 'https://opensource.org/licenses/MIT';
        }

        return $trimmed;
    }
}
