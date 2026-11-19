<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Http\Controllers;

use App\Models\SiteSetting;
use App\Modules\Downloads\Contracts\DownloadSlugHandlerInterface;
use App\Modules\Downloads\Http\Resources\DownloadItemPublicResource;
use App\Modules\Downloads\Http\Resources\DownloadSettingResource;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use App\Modules\Downloads\Services\DownloadCatalogService;
use App\Modules\Downloads\Services\DownloadSeoBridgeService;
use App\Services\PageBuilderResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador/Handler público SSR del catálogo autónomo de Descargas.
 *
 * Respeta estrictamente la visibilidad configurada (`admin_only` por defecto):
 * si el catálogo no es público, responde 404 a visitantes anónimos y muestra
 * una vista previa segura (`noindex`) únicamente a administradores autenticados.
 */
class PublicDownloadSlugHandler implements DownloadSlugHandlerInterface
{
    public function __construct(
        private readonly DownloadCatalogService $catalogService,
        private readonly DownloadSeoBridgeService $seoBridge,
        private readonly PageBuilderResolverService $pageBuilderResolver,
    ) {}

    public function matchesCatalogSlug(string $slug): bool
    {
        $settings = DownloadSetting::cached();

        return $slug !== '' && $slug === $settings->slug;
    }

    /**
     * Renderiza la portada del catálogo o una página paginada limpia (`/{slug}/pagina/{page}`).
     */
    public function renderCatalog(Request $request, int $pageNumber = 1): Response|RedirectResponse
    {
        $dlSettings = DownloadSetting::cached();
        $user = $request->user();

        if (!$dlSettings->isPubliclyAccessible($user)) {
            abort(404);
        }

        $items = $this->catalogService->listarPublicados((int) ($dlSettings->per_page ?: 12), $pageNumber);

        // Blindaje SEO contra páginas vacías fuera de rango
        if ($pageNumber > 1 && $pageNumber > $items->lastPage()) {
            abort(404);
        }

        $siteSettings = SiteSetting::cached();
        $breadcrumb = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => $dlSettings->page_title, 'url' => $dlSettings->publicIndexUrl()],
        ];

        $this->seoBridge->configurarCatalogo($dlSettings, $items, (string) $siteSettings->site_name, $pageNumber, $breadcrumb);

        // Si el administrador activó un diseño visual del Page Builder, resolver sus fuentes dinámicas
        $resolvedBuilderLayout = null;
        $rawBuilderLayout = $dlSettings->layout_json;
        if ((bool) $dlSettings->use_builder_layout && is_array($rawBuilderLayout) && !empty($rawBuilderLayout['sections'])) {
            $resolvedBuilderLayout = $this->pageBuilderResolver->resolve($rawBuilderLayout);
        }

        $mappedItems = $items->getCollection()
            ->map(fn (DownloadItem $item) => (new DownloadItemPublicResource($item, false))->resolve($request))
            ->values()
            ->all();

        $descargasPayload = [
            'data' => $mappedItems,
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
                'links'        => PageBuilderResolverService::formatearLinksPaginacion($items, $dlSettings->slug),
            ],
        ];

        return Inertia::render('Public/Downloads/Index', [
            'catalogo'               => (new DownloadSettingResource($dlSettings, $resolvedBuilderLayout))->resolve($request),
            'descargas'              => $descargasPayload,
            'breadcrumb'             => $breadcrumb,
            'esPrevisualizacionAdmin'=> !$dlSettings->isStrictlyPublic(),
        ]);
    }

    /**
     * Renderiza la ficha individual SEO de una descarga (`/{catalogSlug}/{itemSlug}`).
     */
    public function renderItem(Request $request, string $itemSlug): Response|RedirectResponse
    {
        $dlSettings = DownloadSetting::cached();
        $user = $request->user();

        if (!$dlSettings->isPubliclyAccessible($user)) {
            abort(404);
        }

        $adminRole = (string) config('downloads.admin_role', 'admin');
        $isAdmin = $user !== null && method_exists($user, 'hasRole') && $user->hasRole($adminRole);

        $item = $this->catalogService->obtenerPorSlugParaVista($itemSlug, $isAdmin);
        if (!$item) {
            abort(404);
        }

        $siteSettings = SiteSetting::cached();
        $breadcrumb = [
            ['name' => 'Inicio', 'url' => url('/')],
            ['name' => $dlSettings->page_title, 'url' => $dlSettings->publicIndexUrl()],
            ['name' => $item->title, 'url' => $dlSettings->publicItemUrl($item->slug)],
        ];

        $this->seoBridge->configurarItem($dlSettings, $item, (string) $siteSettings->site_name, $breadcrumb);

        // Incrementar vistas en segundo plano tras enviar el SSR (una única ejecución por petición, sin alterar updated_at)
        if ($item->isPublished() && !$isAdmin) {
            $itemId = (int) $item->getKey();
            $itemsTable = $item->getTable();
            $viewCounted = false;
            app()->terminating(static function () use (&$viewCounted, $itemsTable, $itemId): void {
                if ($viewCounted) {
                    return;
                }
                $viewCounted = true;
                \Illuminate\Support\Facades\DB::table($itemsTable)->where('id', $itemId)->increment('views_count');
            });
        }

        // Si se activó la Plantilla Visual del Page Builder para la ficha individual, resolver tokens `{download.*}`
        $resolvedItemLayout = null;
        $rawItemLayout = $dlSettings->item_layout_json;
        $usesItemBuilder = (bool) $dlSettings->use_item_builder_layout
            && is_array($rawItemLayout)
            && !empty($rawItemLayout['sections']);

        if ($usesItemBuilder) {
            $resolvedItemLayout = $this->pageBuilderResolver->resolveForDownload($rawItemLayout, $item, $dlSettings);
        }

        $props = [
            'catalogo'                => (new DownloadSettingResource($dlSettings, null, $resolvedItemLayout))->resolve($request),
            'descarga'                => (new DownloadItemPublicResource($item, true))->resolve($request),
            'breadcrumb'              => $breadcrumb,
            'esPrevisualizacionAdmin' => !$dlSettings->isStrictlyPublic() || !$item->isPublished(),
        ];

        // Solo cargar relacionadas diferidas en modo editorial clásico; en modo Builder se controlan 100% con secciones editables
        if (!$usesItemBuilder) {
            $props['relacionadas'] = Inertia::defer(function () use ($item, $request) {
                return DownloadItem::publicados()
                    ->where('id', '!=', $item->id)
                    ->orderBy('order', 'asc')
                    ->limit(3)
                    ->get()
                    ->map(fn (DownloadItem $rel) => (new DownloadItemPublicResource($rel, false))->resolve($request))
                    ->values()
                    ->all();
            });
        }

        return Inertia::render('Public/Downloads/Show', $props);
    }
}
