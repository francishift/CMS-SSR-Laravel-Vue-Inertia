<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Services\ArticleService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para las páginas públicas del CMS.
 *
 * URLs limpias para SEO:
 *   /                          → Home
 *   /pagina/{page}             → Home paginada
 *   /{categoria}               → Vista de categoría
 *   /{categoria}/pagina/{page} → Vista de categoría paginada
 *   /{categoria}/{articulo}    → Vista de artículo
 */
class PublicPageController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly SeoService $seoService,
        private readonly \App\Services\PageBuilderResolverService $pageBuilderResolverService,
    ) {}

    /**
     * Buscador Público de Contenido (Artículos y Páginas).
     */
    public function search(Request $request): Response
    {
        $query = trim((string) $request->input('q', ''));
        $settings = SiteSetting::cached();

        $articulosPayload = ['data' => []];
        if ($query !== '') {
            $articulosPaginados = \App\Models\Article::publicados()
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('excerpt', 'like', "%{$query}%")
                      ->orWhere('body', 'like', "%{$query}%");
                })
                ->with(['category', 'author:id,name'])
                ->orderByDesc('published_at')
                ->paginate(12)
                ->withQueryString();

            $articulosPayload = ArticleResource::collection($articulosPaginados)->response()->getData(true);
        }

        $breadcrumb = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => 'Búsqueda', 'url' => url('/buscar')],
        ];

        $this->seoService->configurarBusqueda($query, $settings->site_name, $breadcrumb);

        return Inertia::render('Public/Search', [
            'query'      => $query,
            'articulos'  => $articulosPayload,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * Página de inicio — muestra la página estática marcada como homepage.
     */
    public function home(Request $request): Response|RedirectResponse
    {
        // Redirigir parámetro de query ?page=X a URL limpia /pagina/X para SEO canónico
        if ($request->has('page')) {
            $p = (int) $request->input('page');
            return $p > 1 ? redirect()->to(url("/pagina/{$p}"), 301) : redirect()->to(url('/'), 301);
        }

        return $this->renderHome(1);
    }

    /**
     * Página de inicio paginada limpia (/pagina/{page}).
     */
    public function homePaginated(Request $request, string|int $page): Response|RedirectResponse
    {
        $p = (int) $page;
        if ($p <= 1) {
            return redirect()->to(url('/'), 301);
        }

        return $this->renderHome($p);
    }

    private function renderHome(int $pageNumber = 1): Response
    {
        $settings = SiteSetting::cached();
        $homepage = \App\Models\Page::obtenerHomepage();

        // Si no hay homepage configurada, mostrar una página de bienvenida mínima
        if (!$homepage) {
            $this->seoService->configurarHome(
                $settings->site_name,
                $settings->seo_default_description,
                $settings->seo_default_og_image,
                $pageNumber,
            );

            return Inertia::render('Public/Page', [
                'pagina' => [
                    'id'    => 0,
                    'title' => $settings->site_name ?? 'Bienvenido',
                    'body'  => '<p>Configura una página de inicio desde el panel de administración.</p>',
                    'slug'  => '',
                ],
                'breadcrumb' => [],
            ]);
        }

        // Configurar SEO con los datos de la página de inicio
        $this->seoService->configurarPagina($homepage, $settings->site_name, $pageNumber);

        return Inertia::render('Public/Page', [
            'pagina'               => new \App\Http\Resources\PageResource($homepage),
            'cabeceraTransparente' => $this->extraerConfigCabeceraTransparente($homepage->layout_json),
            'breadcrumb'           => [],
        ]);
    }

    /**
     * Resolver la URL /{slug} pública (Categoría o Página Estática).
     */
    public function resolveSlug(Request $request, string $slug): Response|RedirectResponse
    {
        // Redirigir parámetro de query ?page=X a URL limpia /{slug}/pagina/X para SEO canónico
        if ($request->has('page')) {
            $p = (int) $request->input('page');
            return $p > 1 ? redirect()->to(url("/{$slug}/pagina/{$p}"), 301) : redirect()->to(url("/{$slug}"), 301);
        }

        return $this->renderSlugView($request, $slug, 1);
    }

    /**
     * Resolver la URL /{slug}/pagina/{page} limpia (Categoría o Página Estática paginada).
     */
    public function resolveSlugPaginated(Request $request, string $slug, string|int $page): Response|RedirectResponse
    {
        $pageNumber = (int) $page;
        if ($pageNumber <= 1) {
            return redirect()->to(url("/{$slug}"), 301);
        }

        return $this->renderSlugView($request, $slug, $pageNumber);
    }

    /**
     * Renderiza la vista del slug con el número de página solicitado.
     */
    private function renderSlugView(Request $request, string $slug, int $pageNumber = 1): Response
    {
        $settings = SiteSetting::cached();

        // 1. Comprobar si el slug corresponde a una página estática activa (tienen prioridad sobre las categorías con mismo slug)
        $page = \App\Models\Page::activas()
            ->where('slug', $slug)
            ->first();

        if ($page) {
            if ($request->wantsJson()) {
                return $this->pageContent($slug);
            }

            $breadcrumb = [
                ['name' => 'Inicio', 'url' => url('/')],
                ['name' => $page->title, 'url' => url("/{$page->slug}")],
            ];

            $this->seoService->configurarPagina($page, $settings->site_name, $pageNumber, $breadcrumb);

            return Inertia::render('Public/Page', [
                'pagina'               => new \App\Http\Resources\PageResource($page),
                'cabeceraTransparente' => $this->extraerConfigCabeceraTransparente($page->layout_json),
                'breadcrumb'           => $breadcrumb,
            ]);
        }

        // 2. Comprobar si el slug corresponde a una categoría activa
        $category = Category::activas()
            ->with(['categoryBuilderTemplate'])
            ->where('slug', $slug)
            ->first();

        if ($category) {
            $breadcrumb = [
                ['name' => 'Inicio', 'url' => url('/')],
                ['name' => $category->name, 'url' => url("/{$category->slug}")],
            ];

            $this->seoService->configurarCategoria($category, $settings->site_name, $pageNumber, $breadcrumb);
            $articulos = $this->articleService->publicadosPorCategoria($category->id, $category->per_page, $pageNumber);

            // Blindaje SEO: si se solicita una página superior a la última página real, 404 para evitar contenido duplicado vacío
            if ($pageNumber > 1 && $pageNumber > $articulos->lastPage()) {
                abort(404);
            }

            // Si tiene asignada una plantilla visual del Builder activa, resolver su layout dinámico
            $usaPlantillaBuilder = $category->categoryBuilderTemplate && $category->categoryBuilderTemplate->is_active;
            if ($usaPlantillaBuilder) {
                $category->layout_json = $this->pageBuilderResolverService->resolveForCategory(
                    $category->categoryBuilderTemplate->layout_json,
                    $category,
                    $articulos
                );
            }

            if ($usaPlantillaBuilder && !empty($category->layout_json['sections'])) {
                // Evitar enviar los artículos dos veces cuando ya viajan resueltos dentro de categoria.layout_json:
                // mantener únicamente la metainformación de paginación SEO y posibles fechas de auto-expiración.
                $itemsAutoExpire = $articulos->getCollection()
                    ->filter(fn ($art) => $art->published_until !== null)
                    ->map(fn ($art) => [
                        'id'                  => $art->id,
                        'published_at_iso'    => $art->published_at?->toIso8601String(),
                        'published_until_iso' => $art->published_until?->toIso8601String(),
                    ])
                    ->values()
                    ->all();

                $articulosPayload = [
                    'data' => $itemsAutoExpire,
                    'meta' => [
                        'current_page' => $articulos->currentPage(),
                        'last_page'    => $articulos->lastPage(),
                        'per_page'     => $articulos->perPage(),
                        'total'        => $articulos->total(),
                        'links'        => \App\Services\PageBuilderResolverService::formatearLinksPaginacion($articulos, $category->slug),
                    ],
                ];
            } else {
                $articulosPayload = ArticleResource::collection($articulos)->response()->getData(true);
                $articulosPayload['meta']['links'] = \App\Services\PageBuilderResolverService::formatearLinksPaginacion($articulos, $category->slug);
            }

            return Inertia::render('Public/Category', [
                'categoria'  => new CategoryResource($category),
                'articulos'  => $articulosPayload,
                'breadcrumb' => $breadcrumb,
            ]);
        }

        // 3. Delegar a extensiones modulares autónomas registradas (ej. catálogo de Descargas)
        if (app()->bound(\App\Modules\Downloads\Contracts\DownloadSlugHandlerInterface::class)) {
            $downloadHandler = app(\App\Modules\Downloads\Contracts\DownloadSlugHandlerInterface::class);
            if ($downloadHandler->matchesCatalogSlug($slug)) {
                return $downloadHandler->renderCatalog($request, $pageNumber);
            }
        }

        // 4. No encontrado -> abortar 404
        abort(404);
    }

    /**
     * Vista directa de detalle de un artículo por slug (/articulos/{articleSlug}).
     * Redirige con 301 permanente a su URL canónica oficial (/{categorySlug}/{articleSlug}).
     */
    public function articleBySlug(string $articleSlug): RedirectResponse
    {
        $article = $this->articleService->obtenerPublicadoPorSlug($articleSlug);

        if (!$article || !$article->category || !$article->category->is_active) {
            abort(404);
        }

        return redirect()->to(url("/{$article->category->slug}/{$article->slug}"), 301);
    }

    /**
     * Vista de detalle de un artículo por categoría y slug (o recurso modular de 2 segmentos).
     *
     * URL: /{categorySlug}/{articleSlug}
     */
    public function article(Request $request, string $categorySlug, string $articleSlug): Response|RedirectResponse
    {
        // Delegar a extensión modular autónoma si el primer segmento coincide con su catálogo activo
        if (app()->bound(\App\Modules\Downloads\Contracts\DownloadSlugHandlerInterface::class)) {
            $downloadHandler = app(\App\Modules\Downloads\Contracts\DownloadSlugHandlerInterface::class);
            if ($downloadHandler->matchesCatalogSlug($categorySlug)) {
                return $downloadHandler->renderItem($request, $articleSlug);
            }
        }

        // Obtener artículo publicado dentro del CMS con sus relaciones de plantilla
        $article = $this->articleService->obtenerPublicadoPorSlug($articleSlug);

        if (!$article) {
            abort(404);
        }

        // Cargar la categoría activa del artículo
        $category = $article->category;

        if (!$category || !$category->is_active) {
            abort(404);
        }

        // Blindaje SEO Canónico: Si el slug de la categoría no coincide exactamente, redirigir 301 a la URL canónica
        if ($categorySlug !== $category->slug) {
            return redirect()->to(url("/{$category->slug}/{$article->slug}"), 301);
        }

        $breadcrumb = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => $category->name, 'url' => url("/{$category->slug}")],
            ['name' => $article->title, 'url' => url("/{$category->slug}/{$article->slug}")],
        ];

        $settings = SiteSetting::cached();
        $this->seoService->configurarArticulo($article, $settings->site_name, $breadcrumb);

        // Incrementar vistas de forma atómica
        $article->incrementarVistas();

        // Comprobar jerarquía de plantillas del Builder (1. Artículo -> 2. Categoría)
        $articleTemplate = $article->articleBuilderTemplate;
        if (!$articleTemplate && $category->article_builder_template_id) {
            $articleTemplate = $category->articleBuilderTemplate;
        }

        if ($articleTemplate && $articleTemplate->is_active) {
            $article->layout_json = $this->pageBuilderResolverService->resolveForArticle(
                $articleTemplate->layout_json,
                $article
            );
        }

        return Inertia::render('Public/Article', [
            // Datos inmediatos — críticos para SEO (SSR los renderiza en el HTML inicial)
            'articulo'     => new ArticleResource($article),
            'breadcrumb'   => $breadcrumb,

            // Datos diferidos — NO críticos para SEO, se cargan tras el render inicial
            'relacionados' => Inertia::defer(fn () => ArticleResource::collection(
                $this->articleService->relacionados($article)
            )),
            'navegacion'   => Inertia::defer(fn () => $this->articleService->obtenerNavegacionAnteriorYSiguiente($article, $category)),
        ]);
    }

    /**
     * Extrae la configuración de cabecera transparente de la primera sección del layout JSON.
     */
    private function extraerConfigCabeceraTransparente(?array $layoutJson): ?array
    {
        if (empty($layoutJson) || !isset($layoutJson['sections'][0]['settings'])) {
            return null;
        }

        $s = $layoutJson['sections'][0]['settings'];
        if (empty($s['transparent_header'])) {
            return null;
        }

        return [
            'active'   => true,
            'pull'     => (bool) ($s['transparent_header_pull'] ?? false),
            'navColor' => $s['transparent_header_nav_color'] ?? 'inherit',
        ];
    }

    /**
     * Devuelve el contenido limpio de una página estática para su visualización en modal.
     * Excluye cabeceras, menús y pies de página.
     */
    public function pageContent(string $slug): \Illuminate\Http\JsonResponse
    {
        $slugLimpio = trim(urldecode($slug), '/');

        $page = \App\Models\Page::activas()
            ->where('slug', $slugLimpio)
            ->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Página no encontrada o inactiva.',
            ], 404);
        }

        $resolver = new \App\Services\PageBuilderResolverService();

        return response()->json([
            'success'     => true,
            'title'       => $page->title,
            'title_size'  => $page->title_size ?: 'default',
            'slug'        => $page->slug,
            'body'        => $page->body,
            'layout_json' => $page->layout_json ? $resolver->resolve($page->layout_json) : null,
        ]);
    }
}
