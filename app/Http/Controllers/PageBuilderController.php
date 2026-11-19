<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PageResource;
use App\Models\Article;
use App\Models\BuilderTemplate;
use App\Models\Category;
use App\Models\Page;
use App\Services\CacheInvalidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador del Constructor Visual de Páginas (Page Builder) para la administración.
 */
class PageBuilderController extends Controller
{
    /**
     * Muestra la interfaz del maquetador visual dividida (Inspector + Previsualizador).
     */
    public function edit(Page $page): Response
    {
        $categories = Category::activas()
            ->select('id', 'name', 'slug', 'description', 'featured_image', 'icon', 'color', 'order', 'extra_fields_schema', 'category_builder_template_id', 'article_builder_template_id')
            ->orderBy('order', 'asc')
            ->get();

        $articles = Article::publicados()
            ->select('id', 'title', 'slug', 'excerpt', 'body', 'featured_image', 'icon', 'order', 'category_id', 'author_id', 'published_at', 'reading_time', 'extra_fields_data', 'article_builder_template_id')
            ->with(['category:id,name,slug,extra_fields_schema,category_builder_template_id,article_builder_template_id', 'author:id,name'])
            ->orderByDesc('published_at')
            ->take(50)
            ->get();

        $paginasList = \App\Services\PageBuilderResolverService::construirPaginasListBuilder();

        return Inertia::render('Paginas/Builder', [
            'pagina'             => (new PageResource($page))->resolve(),
            'categorias'         => CategoryResource::collection($categories)->resolve(),
            'articulosMuestra'   => ArticleResource::collection($articles)->resolve(),
            'descargasMuestra'   => \App\Services\PageBuilderResolverService::obtenerDescargasMuestraBuilder(),
            'paginasList'        => $paginasList,
            'rutaGuardarBuilder' => route('paginas.builder.update', $page->id),
            'rutaPreviewFrame'   => route('paginas.builder.preview-frame', $page->id),
        ]);
    }

    /**
     * Entrega el marco aislado de previsualización en tiempo real para el iframe del Builder.
     */
    public function previewFrame(Page $page): Response
    {
        $categories = Category::activas()
            ->select('id', 'name', 'slug', 'description', 'featured_image', 'icon', 'color', 'order', 'extra_fields_schema', 'category_builder_template_id', 'article_builder_template_id')
            ->orderBy('order', 'asc')
            ->get();

        $articles = Article::publicados()
            ->select('id', 'title', 'slug', 'excerpt', 'body', 'featured_image', 'icon', 'order', 'category_id', 'author_id', 'published_at', 'reading_time', 'extra_fields_data')
            ->with(['category:id,name,slug,extra_fields_schema', 'author:id,name'])
            ->orderByDesc('published_at')
            ->take(50)
            ->get();

        $resolver = new \App\Services\PageBuilderResolverService();
        $resolvedLayout = $resolver->resolve($page->layout_json ?? []);

        return Inertia::render('Paginas/BuilderPreviewFrame', [
            'pagina'           => (new PageResource($page))->resolve(),
            'initialLayout'    => $resolvedLayout,
            'categorias'       => CategoryResource::collection($categories)->resolve(),
            'articulosMuestra' => ArticleResource::collection($articles)->resolve(),
            'descargasMuestra' => \App\Services\PageBuilderResolverService::obtenerDescargasMuestraBuilder(),
        ]);
    }

    /**
     * Guarda la estructura JSON del maquetador e invalida la caché de Redis.
     */
    public function update(Request $request, Page $page): RedirectResponse
    {
        $validated = $request->validate([
            'layout_json'     => ['nullable', 'array'],
            'seo_title'       => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'seo_og_image'    => ['nullable', 'string', 'max:2048'],
            'meta_robots'     => ['nullable', 'string', 'max:50'],
        ]);

        $layoutJson = $validated['layout_json'] ?? null;
        if (is_array($layoutJson)) {
            $layoutJson = $this->sanitizarLayout($layoutJson);
            $layoutJson = $this->normalizarElementosLayout($layoutJson);
        }

        $page->update([
            'layout_json'     => $layoutJson,
            'seo_title'       => $validated['seo_title'] ?? $page->seo_title,
            'seo_description' => $validated['seo_description'] ?? $page->seo_description,
            'seo_og_image'    => $validated['seo_og_image'] ?? $page->seo_og_image,
            'meta_robots'     => array_key_exists('meta_robots', $validated) ? $validated['meta_robots'] : $page->meta_robots,
        ]);

        CacheInvalidationService::limpiarCachePublica();

        return back()->with('success', 'Estructura y metadatos SEO guardados correctamente.');
    }

    /**
     * Asegura la integridad estructural de los elementos del layout, evitando que propiedades
     * como 'content' queden como arrays vacíos o nulos en elementos estructurados como 'gallery'.
     */
    protected function normalizarElementosLayout(array $layout): array
    {
        if (!isset($layout['sections']) || !is_array($layout['sections'])) {
            return $layout;
        }

        foreach ($layout['sections'] as &$section) {
            if (!isset($section['rows']) || !is_array($section['rows'])) {
                continue;
            }
            foreach ($section['rows'] as &$row) {
                if (!isset($row['columns']) || !is_array($row['columns'])) {
                    continue;
                }
                foreach ($row['columns'] as &$column) {
                    if (!isset($column['elements']) || !is_array($column['elements'])) {
                        continue;
                    }
                    foreach ($column['elements'] as &$element) {
                        $this->normalizarElemento($element);
                    }
                    unset($element);
                }
                unset($column);
            }
            unset($row);
        }
        unset($section);

        return $layout;
    }

    protected function normalizarElemento(array &$element): void
    {
        if (!isset($element['type'])) {
            return;
        }

        if (!isset($element['content']) || !is_array($element['content'])) {
            $element['content'] = [];
        }

        if ($element['type'] === 'gallery') {
            if (empty($element['content'])) {
                $element['content'] = [
                    'mode'  => 'manual',
                    'items' => [],
                ];
            } elseif (!isset($element['content']['items']) || !is_array($element['content']['items'])) {
                $element['content']['items'] = [];
            }
        }

        // Sublayouts anidados recursivos
        if ($element['type'] === 'sublayout' && !empty($element['content']['rows']) && is_array($element['content']['rows'])) {
            foreach ($element['content']['rows'] as &$subrow) {
                foreach ($subrow['columns'] ?? [] as &$subcol) {
                    foreach ($subcol['elements'] ?? [] as &$subelem) {
                        $this->normalizarElemento($subelem);
                    }
                }
            }
        }
    }

    /**
     * Sanitiza recursivamente strings dentro de layout_json para eliminar &nbsp; y caracteres invisibles.
     */
    protected function sanitizarLayout(array $layout): array
    {
        array_walk_recursive($layout, function (&$value) {
            if (is_string($value)) {
                $value = str_replace(
                    ['&nbsp;', "\xC2\xA0", "\xC2\xAD", '&shy;'],
                    [' ', ' ', '', ''],
                    $value
                );
            }
        });

        return $layout;
    }

    /**
     * Endpoint API para previsualizar datos dinámicos en tiempo real dentro del editor.
     */
    public function resolvePreview(Request $request): JsonResponse
    {
        $layout = $request->input('layout_json', []);
        $resolver = new \App\Services\PageBuilderResolverService();
        $resolved = $resolver->resolve($layout);

        return response()->json([
            'success' => true,
            'layout'  => $resolved,
        ]);
    }

    /**
     * Obtiene muestra ligera de descargas si la extensión autónoma está instalada en el portal.
     */
    private function obtenerDescargasMuestra(): array
    {
        if (
            !class_exists(\App\Modules\Downloads\Models\DownloadItem::class) ||
            !class_exists(\App\Modules\Downloads\Http\Resources\DownloadItemPublicResource::class)
        ) {
            return [];
        }

        try {
            $req = request();

            return \App\Modules\Downloads\Models\DownloadItem::publicados()
                ->orderBy('order', 'asc')
                ->take(30)
                ->get()
                ->map(function ($item) use ($req) {
                    $data = (new \App\Modules\Downloads\Http\Resources\DownloadItemPublicResource($item, false))->resolve($req);
                    $data['excerpt'] = $data['short_description'] ?? '';
                    $data['_source'] = 'downloads';

                    return $data;
                })
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
