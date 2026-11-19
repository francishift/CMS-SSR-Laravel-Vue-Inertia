<?php

namespace App\Services;

use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;
use Spatie\SchemaOrg\Schema;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Article;
use App\Models\Category;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio centralizado de SEO.
 *
 * Encapsula toda la lógica de meta tags, Open Graph, Twitter Cards, JSON-LD
 * y generación de sitemaps. Los controladores solo llaman
 * a métodos descriptivos sin preocuparse de la implementación.
 */
class SeoService
{
    /**
     * Nodos acumulados para el esquema estructurado unificado Schema.org (@graph).
     *
     * @var array<string, array<string, mixed>>
     */
    private array $graphNodes = [];

    /**
     * Extensiones modulares registradas para enriquecer el sitemap XML sin acoplamiento duro.
     *
     * @var array<int, callable(Sitemap): void>
     */
    private static array $sitemapExtenders = [];

    /**
     * Permite a módulos autónomos registrar un proveedor de URLs para el sitemap XML.
     *
     * @param  callable(Sitemap): void  $extender
     */
    public static function registerSitemapExtender(callable $extender): void
    {
        self::$sitemapExtenders[] = $extender;
    }

    /**
     * Inicializa las entidades base del grafo Schema.org, Twitter site y migas de pan para un módulo externo.
     *
     * @param  array<int, array{name: string, url: string}>  $breadcrumb
     */
    public function inicializarContextoModulo(string $siteName, ?string $siteDesc = null, array $breadcrumb = [], ?string $canonicalUrl = null): void
    {
        $settings = SiteSetting::cached();
        $this->aplicarTwitterSite($settings);
        $this->agregarEntidadesBase($siteName, $settings, $siteDesc);

        if (!empty($breadcrumb) && !empty($canonicalUrl)) {
            $this->agregarBreadcrumbList($breadcrumb, $canonicalUrl);
        }
    }

    /**
     * Registra un nodo personalizado dentro del grafo unificado Schema.org (@graph).
     *
     * @param  array<string, mixed>  $node
     */
    public function registrarNodoGraph(string $key, array $node): void
    {
        $this->graphNodes[$key] = $node;
    }

    /**
     * Configurar SEO para la página de inicio.
     */
    public function configurarHome(string $siteName, ?string $description = null, ?string $ogImage = null, int $pageNumber = 1): void
    {
        $settings = SiteSetting::cached();
        $desc = $description ?: $settings->seo_default_description ?: $settings->site_description ?: "Bienvenido a {$siteName}";
        if ($pageNumber > 1) {
            $desc = "{$desc} — Página {$pageNumber}";
        }

        $title = $pageNumber > 1 ? "{$siteName} — Página {$pageNumber}" : $siteName;
        $canonicalUrl = $pageNumber > 1 ? url("/pagina/{$pageNumber}") : url('/');

        SEOMeta::setTitle($title, false);
        SEOMeta::setDescription($desc);
        SEOMeta::setCanonical($canonicalUrl);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($desc);
        OpenGraph::setUrl($canonicalUrl);
        OpenGraph::addProperty('type', 'website');
        OpenGraph::addProperty('site_name', $siteName);

        $image = $ogImage ?: $settings->seo_default_og_image ?: $settings->logo_path;
        if ($image) {
            OpenGraph::addImage(url($image));
            JsonLd::addImage(url($image));
        }

        // Twitter Cards (Summary Large Image)
        TwitterCard::setTitle($title);
        TwitterCard::setDescription($desc);
        TwitterCard::setUrl($canonicalUrl);
        TwitterCard::setType('summary_large_image');
        if ($image) {
            TwitterCard::setImage(url($image));
        }
        $this->aplicarTwitterSite($settings);

        // Construir nodos base del grafo: Organization + WebSite
        $this->agregarEntidadesBase($siteName, $settings, $desc);
    }

    /**
     * Configurar SEO para una vista de categoría.
     *
     * @param  array<int, array{name: string, url: string}>  $breadcrumb
     */
    public function configurarCategoria(Category $categoria, string $siteName, int $page = 1, array $breadcrumb = []): void
    {
        $settings = SiteSetting::cached();
        $baseTitle = !empty($categoria->seo_title) ? $categoria->seo_title : $categoria->name;
        $title = $page > 1 ? "{$baseTitle} — Página {$page}" : $baseTitle;

        $description = !empty($categoria->seo_description) ? $categoria->seo_description : ($categoria->description ?? '');
        if (empty($description)) {
            $description = $settings->seo_default_description ?: $settings->site_description ?: "Categoría: {$categoria->name}";
        }
        if ($page > 1) {
            $description = "{$description} — Página {$page}";
        }

        $canonicalUrl = $page > 1 ? url("/{$categoria->slug}/pagina/{$page}") : url("/{$categoria->slug}");

        SEOMeta::setTitle($title, false);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($canonicalUrl);

        // Directiva robots personalizada (noindex/nofollow)
        if (!empty($categoria->meta_robots)) {
            SEOMeta::setRobots($categoria->meta_robots);
        }

        OpenGraph::setTitle("{$title} - {$siteName}");
        OpenGraph::setDescription($description);
        OpenGraph::setUrl($canonicalUrl);
        OpenGraph::addProperty('type', 'website');
        OpenGraph::addProperty('site_name', $siteName);

        $ogImage = $categoria->seo_og_image ?? $categoria->featured_image ?? $settings->seo_default_og_image;
        if ($ogImage) {
            OpenGraph::addImage(url($ogImage));
            JsonLd::addImage(url($ogImage));
        }

        // Twitter Cards
        TwitterCard::setTitle("{$title} - {$siteName}");
        TwitterCard::setDescription($description);
        TwitterCard::setUrl($canonicalUrl);
        TwitterCard::setType('summary_large_image');
        if ($ogImage) {
            TwitterCard::setImage(url($ogImage));
        }
        $this->aplicarTwitterSite($settings);

        // Nodos del Grafo Schema.org
        $this->agregarEntidadesBase($siteName, $settings);
        if (!empty($breadcrumb)) {
            $this->agregarBreadcrumbList($breadcrumb, $canonicalUrl);
        }

        $collectionNode = [
            '@type'       => 'CollectionPage',
            '@id'         => $canonicalUrl . '#collection',
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

        $this->graphNodes['collection'] = $collectionNode;
    }

    /**
     * Configurar SEO para una página estática (soporta paginación limpia).
     *
     * @param  array<int, array{name: string, url: string}>  $breadcrumb
     */
    public function configurarPagina(\App\Models\Page $page, string $siteName, int $pageNumber = 1, array $breadcrumb = []): void
    {
        $settings = SiteSetting::cached();
        $baseTitle = !empty($page->seo_title) ? $page->seo_title : ($page->is_homepage ? $siteName : $page->title);
        $title = $pageNumber > 1 ? "{$baseTitle} — Página {$pageNumber}" : $baseTitle;

        $description = !empty($page->seo_description) ? $page->seo_description : null;

        if (empty($description) && !empty($page->body)) {
            $cleaned = trim(strip_tags($page->body));
            if (!empty($cleaned)) {
                $description = mb_substr($cleaned, 0, 160);
            }
        }

        if (empty($description)) {
            $description = $settings->seo_default_description ?: $settings->site_description ?: "Bienvenido a {$siteName}";
        }

        if ($pageNumber > 1) {
            $description = "{$description} — Página {$pageNumber}";
        }

        $canonicalUrl = $page->is_homepage
            ? ($pageNumber > 1 ? url("/pagina/{$pageNumber}") : url('/'))
            : ($pageNumber > 1 ? url("/{$page->slug}/pagina/{$pageNumber}") : url("/{$page->slug}"));

        SEOMeta::setTitle($title, false);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($canonicalUrl);

        // Directiva robots personalizada (noindex/nofollow)
        if (!empty($page->meta_robots)) {
            SEOMeta::setRobots($page->meta_robots);
        }

        $pageTitleFormatted = $page->is_homepage ? $title : "{$title} - {$siteName}";

        OpenGraph::setTitle($pageTitleFormatted);
        OpenGraph::setDescription($description);
        OpenGraph::setUrl($canonicalUrl);
        OpenGraph::addProperty('type', 'website');
        OpenGraph::addProperty('site_name', $siteName);

        $ogImage = $page->seo_og_image ?: $settings->seo_default_og_image;
        if ($ogImage) {
            OpenGraph::addImage(url($ogImage));
            JsonLd::addImage(url($ogImage));
        }

        // Twitter Cards
        TwitterCard::setTitle($pageTitleFormatted);
        TwitterCard::setDescription($description);
        TwitterCard::setUrl($canonicalUrl);
        TwitterCard::setType('summary_large_image');
        if ($ogImage) {
            TwitterCard::setImage(url($ogImage));
        }
        $this->aplicarTwitterSite($settings);

        // Nodos del Grafo Schema.org
        $this->agregarEntidadesBase($siteName, $settings, $description);

        if ($page->is_homepage) {
            $this->graphNodes['webpage'] = [
                '@type'       => 'WebPage',
                '@id'         => url('/#webpage'),
                'url'         => url('/'),
                'name'        => $title,
                'description' => $description,
                'isPartOf'    => ['@id' => url('/#website')],
                'inLanguage'  => str_replace('_', '-', app()->getLocale()),
                'about'       => ['@id' => url('/#organization')],
            ];
        } else {
            if (!empty($breadcrumb)) {
                $this->agregarBreadcrumbList($breadcrumb, $canonicalUrl);
            }

            $webpageNode = [
                '@type'         => 'WebPage',
                '@id'           => $canonicalUrl . '#webpage',
                'isPartOf'      => ['@id' => url('/#website')],
                'name'          => $title,
                'description'   => $description,
                'url'           => $canonicalUrl,
                'inLanguage'    => str_replace('_', '-', app()->getLocale()),
                'publisher'     => ['@id' => url('/#organization')],
                'datePublished' => $page->created_at?->toIso8601String(),
                'dateModified'  => $page->updated_at?->toIso8601String(),
            ];

            if ($ogImage) {
                $webpageNode['image'] = [url($ogImage)];
            }

            if (!empty($page->body)) {
                $webpageNode['wordCount'] = str_word_count(strip_tags($page->body));
            }

            if (!empty($breadcrumb)) {
                $webpageNode['breadcrumb'] = ['@id' => $canonicalUrl . '#breadcrumb'];
            }

            $this->graphNodes['webpage'] = $webpageNode;
        }
    }

    /**
     * Configurar SEO para un artículo individual.
     *
     * @param  array<int, array{name: string, url: string}>  $breadcrumb
     */
    public function configurarArticulo(Article $articulo, string $siteName, array $breadcrumb = []): void
    {
        $settings = SiteSetting::cached();
        $title = !empty($articulo->seo_title) ? $articulo->seo_title : $articulo->title;
        $description = !empty($articulo->seo_description) ? $articulo->seo_description : ($articulo->excerpt ?? '');
        if (empty($description) && !empty($articulo->body)) {
            $cleaned = trim(strip_tags($articulo->body));
            if (!empty($cleaned)) {
                $description = mb_substr($cleaned, 0, 160);
            }
        }
        if (empty($description)) {
            $description = $settings->seo_default_description ?: $settings->site_description ?: $title;
        }

        SEOMeta::setTitle($title, false);
        SEOMeta::setDescription($description);

        // Directiva robots personalizada (noindex/nofollow)
        if (!empty($articulo->meta_robots)) {
            SEOMeta::setRobots($articulo->meta_robots);
        }
        $categorySlug = $articulo->category?->slug ?? '';
        $articleUrl = $categorySlug ? url("/{$categorySlug}/{$articulo->slug}") : url("/{$articulo->slug}");

        SEOMeta::setCanonical($articleUrl);

        OpenGraph::setTitle("{$title} - {$siteName}");
        OpenGraph::setDescription($description);
        OpenGraph::setUrl($articleUrl);
        OpenGraph::addProperty('type', 'article');
        OpenGraph::addProperty('site_name', $siteName);
        OpenGraph::addProperty('article:published_time', $articulo->published_at?->toIso8601String());
        OpenGraph::addProperty('article:modified_time', $articulo->updated_at?->toIso8601String());

        if ($articulo->category) {
            OpenGraph::addProperty('article:section', $articulo->category->name);
        }

        $ogImage = $articulo->seo_og_image ?? $articulo->featured_image ?? $settings->seo_default_og_image;
        if ($ogImage) {
            OpenGraph::addImage(url($ogImage));
            JsonLd::addImage(url($ogImage));
        }

        // Twitter Cards
        TwitterCard::setTitle("{$title} - {$siteName}");
        TwitterCard::setDescription($description);
        TwitterCard::setUrl($articleUrl);
        TwitterCard::setType('summary_large_image');
        if ($ogImage) {
            TwitterCard::setImage(url($ogImage));
        }
        $this->aplicarTwitterSite($settings);

        // Nodos del Grafo Schema.org
        $this->agregarEntidadesBase($siteName, $settings);
        if (!empty($breadcrumb)) {
            $this->agregarBreadcrumbList($breadcrumb, $articleUrl);
        }

        $articleNode = [
            '@type'            => 'Article',
            '@id'              => $articleUrl . '#article',
            'isPartOf'         => ['@id' => url('/#website')],
            'headline'         => $title,
            'description'      => $description,
            'url'              => $articleUrl,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $articleUrl,
            ],
            'datePublished'    => $articulo->published_at?->toIso8601String(),
            'dateModified'     => $articulo->updated_at?->toIso8601String(),
            'publisher'        => ['@id' => url('/#organization')],
            'inLanguage'       => str_replace('_', '-', app()->getLocale()),
        ];

        if ($articulo->author) {
            $articleNode['author'] = [
                '@type' => 'Person',
                'name'  => $articulo->author->name,
                'url'   => url('/'),
            ];
        }

        if ($ogImage) {
            $articleNode['image'] = [url($ogImage)];
        }

        if ($articulo->category) {
            $articleNode['articleSection'] = $articulo->category->name;
        }

        if (!empty($articulo->body)) {
            $articleNode['wordCount'] = str_word_count(strip_tags($articulo->body));
        }

        if ($articulo->reading_time) {
            $articleNode['timeRequired'] = "PT{$articulo->reading_time}M";
        }

        if (!empty($breadcrumb)) {
            $articleNode['breadcrumb'] = ['@id' => $articleUrl . '#breadcrumb'];
        }

        $this->graphNodes['article'] = $articleNode;
    }

    /**
     * Configurar SEO para la página de resultados de búsqueda.
     *
     * @param  array<int, array{name: string, url: string}>  $breadcrumb
     */
    public function configurarBusqueda(string $query, string $siteName, array $breadcrumb = []): void
    {
        $settings = SiteSetting::cached();
        $title = $query !== '' ? "Resultados para '{$query}'" : "Buscador de contenidos";
        $description = "Buscador y archivo de publicaciones en {$siteName}";
        $url = url('/buscar');

        SEOMeta::setTitle("{$title} - {$siteName}", false);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);
        SEOMeta::setRobots('noindex, follow');

        OpenGraph::setTitle("{$title} - {$siteName}");
        OpenGraph::setDescription($description);
        OpenGraph::setUrl($url);
        OpenGraph::addProperty('type', 'website');
        OpenGraph::addProperty('site_name', $siteName);

        TwitterCard::setTitle("{$title} - {$siteName}");
        TwitterCard::setDescription($description);
        TwitterCard::setUrl($url);
        TwitterCard::setType('summary_large_image');
        $this->aplicarTwitterSite($settings);

        $this->agregarEntidadesBase($siteName, $settings);
        if (!empty($breadcrumb)) {
            $this->agregarBreadcrumbList($breadcrumb, $url);
        }

        $searchNode = [
            '@type'       => 'SearchResultsPage',
            '@id'         => $url . '#searchresults',
            'isPartOf'    => ['@id' => url('/#website')],
            'name'        => "{$title} - {$siteName}",
            'description' => $description,
            'url'         => $query !== '' ? ($url . '?q=' . urlencode($query)) : $url,
            'inLanguage'  => str_replace('_', '-', app()->getLocale()),
        ];

        if (!empty($breadcrumb)) {
            $searchNode['breadcrumb'] = ['@id' => $url . '#breadcrumb'];
        }

        $this->graphNodes['search'] = $searchNode;
    }

    /**
     * Agrega los nodos Organization y WebSite interconectados al grafo Schema.org.
     */
    private function agregarEntidadesBase(string $siteName, SiteSetting $settings, ?string $siteDesc = null): void
    {
        $desc = $siteDesc ?: ($settings->seo_default_description ?: $settings->site_description ?: "Sitio oficial de {$siteName}");

        $socialLinks = array_values(array_filter(
            is_array($settings->social_links) ? $settings->social_links : [],
            fn ($u) => !empty($u) && filter_var($u, FILTER_VALIDATE_URL)
        ));

        $org = [
            '@type' => 'Organization',
            '@id'   => url('/#organization'),
            'name'  => $siteName,
            'url'   => url('/'),
        ];
        if ($settings->logo_path) {
            $org['logo'] = [
                '@type'      => 'ImageObject',
                '@id'        => url('/#logo'),
                'url'        => url($settings->logo_path),
                'contentUrl' => url($settings->logo_path),
                'caption'    => $siteName,
            ];
            $org['image'] = ['@id' => url('/#logo')];
        }
        if (!empty($socialLinks)) {
            $org['sameAs'] = $socialLinks;
        }

        // ContactPoint para atención al usuario si hay datos configurados
        $contactPoints = [];
        if (!empty($settings->contact_phone) || !empty($settings->contact_email)) {
            $cp = [
                '@type'       => 'ContactPoint',
                'contactType' => 'customer service',
            ];
            if (!empty($settings->contact_phone)) {
                $cp['telephone'] = $settings->contact_phone;
            }
            if (!empty($settings->contact_email)) {
                $cp['email'] = $settings->contact_email;
            }
            $contactPoints[] = $cp;
        }
        if (!empty($contactPoints)) {
            $org['contactPoint'] = $contactPoints;
        }

        $this->graphNodes['organization'] = $org;

        $website = [
            '@type'           => 'WebSite',
            '@id'             => url('/#website'),
            'url'             => url('/'),
            'name'            => $siteName,
            'description'     => $desc,
            'inLanguage'      => str_replace('_', '-', app()->getLocale()),
            'publisher'       => ['@id' => url('/#organization')],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => url('/buscar') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        $this->graphNodes['website'] = $website;
    }

    /**
     * Agrega el nodo BreadcrumbList con IDs y posiciones correlativas al grafo Schema.org.
     *
     * @param  array<int, array{name: string, url: string}>  $items
     */
    private function agregarBreadcrumbList(array $items, string $pageUrl): void
    {
        $listItems = [];
        foreach ($items as $index => $item) {
            $listItems[] = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $item['name'],
                'item'     => $item['url'],
            ];
        }

        $this->graphNodes['breadcrumb'] = [
            '@type'           => 'BreadcrumbList',
            '@id'             => $pageUrl . '#breadcrumb',
            'itemListElement' => $listItems,
        ];
    }

    /**
     * Renderiza un único bloque <script type="application/ld+json"> limpio con la estructura @graph unificada.
     */
    public function renderSchemaJsonLd(): string
    {
        if (empty($this->graphNodes)) {
            return '';
        }

        $payload = [
            '@context' => 'https://schema.org',
            '@graph'   => array_values($this->graphNodes),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return "<script type=\"application/ld+json\">{$json}</script>";
    }

    /**
     * Generar el breadcrumb como JSON-LD.
     *
     * @param  array<int, array{name: string, url: string}>  $items
     */
    public function generarBreadcrumb(array $items): string
    {
        $listItems = [];

        foreach ($items as $index => $item) {
            $listItems[] = Schema::listItem()
                ->position($index + 1)
                ->name($item['name'])
                ->item($item['url']);
        }

        return json_encode(
            Schema::breadcrumbList()
                ->itemListElement($listItems)
                ->toArray(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Extrae el handle de usuario de Twitter/X desde los enlaces sociales y lo asocia al TwitterCard.
     */
    private function aplicarTwitterSite(SiteSetting $settings): void
    {
        $twitterUrl = is_array($settings->social_links) ? ($settings->social_links['twitter'] ?? null) : null;
        if (!empty($twitterUrl)) {
            $cleanHandle = preg_replace('#^https?://(www\.)?(twitter\.com|x\.com)/#i', '', trim((string) $twitterUrl));
            $cleanHandle = ltrim($cleanHandle, '@');
            $cleanHandle = explode('/', $cleanHandle)[0];
            $cleanHandle = explode('?', $cleanHandle)[0];
            if (!empty($cleanHandle)) {
                TwitterCard::setSite('@' . $cleanHandle);
            }
        }
    }

    /**
     * Generar sitemap XML completo del sitio.
     *
     * Se programa vía scheduler para ejecutarse diariamente.
     * Incluye: home, páginas estáticas activas, categorías activas y artículos publicados válidos.
     * Excluye automáticamente cualquier recurso con directiva "noindex".
     * Agrega imágenes destacadas a cada URL para potenciar Google Imágenes y Discover.
     */
    public function generarSitemap(): void
    {
        $sitemap = Sitemap::create();

        // 1. Página de inicio
        $sitemap->add(
            Url::create('/')
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0)
        );

        // 2. Páginas estáticas activas (excluyendo homepage y URLs con noindex)
        $paginas = \App\Models\Page::activas()
            ->where('is_homepage', false)
            ->where(function ($q) {
                $q->whereNull('meta_robots')
                  ->orWhere('meta_robots', 'not like', '%noindex%');
            })
            ->orderBy('order')
            ->get();

        foreach ($paginas as $pagina) {
            $urlTag = Url::create("/{$pagina->slug}")
                ->setLastModificationDate($pagina->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8);

            if (!empty($pagina->seo_og_image)) {
                $urlTag->addImage(url($pagina->seo_og_image), $pagina->title);
            }

            $sitemap->add($urlTag);
        }

        // 3. Categorías activas (excluyendo URLs con noindex)
        $categorias = Category::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('meta_robots')
                  ->orWhere('meta_robots', 'not like', '%noindex%');
            })
            ->get();

        foreach ($categorias as $categoria) {
            $urlTag = Url::create("/{$categoria->slug}")
                ->setLastModificationDate($categoria->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8);

            $catImg = $categoria->seo_og_image ?? $categoria->featured_image;
            if ($catImg) {
                $urlTag->addImage(url($catImg), $categoria->name);
            }

            $sitemap->add($urlTag);
        }

        // 4. Artículos publicados válidos (excluye borradores, futuros, expirados y noindex)
        $articulos = Article::publicados()
            ->where(function ($q) {
                $q->whereNull('meta_robots')
                  ->orWhere('meta_robots', 'not like', '%noindex%');
            })
            ->with('category:id,slug')
            ->orderByDesc('published_at')
            ->get();

        foreach ($articulos as $articulo) {
            $categorySlug = $articulo->category?->slug;
            $articleUrl = $categorySlug ? "/{$categorySlug}/{$articulo->slug}" : "/{$articulo->slug}";

            $urlTag = Url::create($articleUrl)
                ->setLastModificationDate($articulo->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.6);

            $artImg = $articulo->seo_og_image ?? $articulo->featured_image;
            if ($artImg) {
                $urlTag->addImage(url($artImg), $articulo->title);
            }

            $sitemap->add($urlTag);
        }

        // 5. Extensiones modulares autónomas (ej. módulo de Descargas cuando es público)
        foreach (self::$sitemapExtenders as $extender) {
            $extender($sitemap);
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
