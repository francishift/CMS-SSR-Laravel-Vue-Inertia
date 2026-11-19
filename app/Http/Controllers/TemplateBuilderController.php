<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Http\Resources\BuilderTemplateResource;
use App\Http\Resources\CategoryResource;
use App\Models\Article;
use App\Models\BuilderTemplate;
use App\Models\Category;
use App\Models\Page;
use App\Services\CacheInvalidationService;
use App\Services\PageBuilderResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador del Maquetador Visual para Plantillas Dinámicas de Categorías y Artículos.
 */
class TemplateBuilderController extends Controller
{
    protected PageBuilderResolverService $resolverService;

    public function __construct(PageBuilderResolverService $resolverService)
    {
        $this->resolverService = $resolverService;
    }

    /**
     * Muestra la interfaz del maquetador visual configurada en modo Plantilla.
     */
    public function edit(BuilderTemplate $template, Request $request): Response
    {
        $previewCategoryId = $request->query('preview_category_id', $template->preview_category_id);
        $previewArticleId  = $request->query('preview_article_id', $template->preview_article_id);

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

        $paginasList = PageBuilderResolverService::construirPaginasListBuilder();

        [$article, $category] = $this->resolveSampleEntities(
            $template,
            $previewArticleId,
            $previewCategoryId,
            $categories,
            $articles
        );

        $previewParams = [];
        if ($previewArticleId) {
            $previewParams['preview_article_id'] = $previewArticleId;
        }
        if ($previewCategoryId) {
            $previewParams['preview_category_id'] = $previewCategoryId;
        }

        return Inertia::render('Paginas/Builder', [
            'pagina'             => [
                'id'          => $template->id,
                'title'       => $template->name,
                'slug'        => "plantilla-{$template->id}",
                'layout_json' => $template->layout_json,
                'type'        => $template->type,
            ],
            'esPlantilla'        => true,
            'tipoPlantilla'      => $template->type,
            'plantilla'          => (new BuilderTemplateResource($template))->resolve(),
            'categoriaMuestra'   => $category ? (new CategoryResource($category))->resolve() : null,
            'articuloMuestra'    => $article ? (new ArticleResource($article))->resolve() : null,
            'categorias'         => CategoryResource::collection($categories)->resolve(),
            'articulosMuestra'   => ArticleResource::collection($articles)->resolve(),
            'descargasMuestra'   => PageBuilderResolverService::obtenerDescargasMuestraBuilder(),
            'paginasList'        => $paginasList,
            'rutaGuardarBuilder' => route('plantillas.builder.update', $template->id),
            'rutaPreviewFrame'   => route('plantillas.builder.preview-frame', array_merge(['template' => $template->id], $previewParams)),
        ]);
    }

    /**
     * Entrega el marco aislado de previsualización resolviendo datos dinámicos en vivo.
     */
    public function previewFrame(BuilderTemplate $template, Request $request): Response
    {
        $previewCategoryId = $request->query('preview_category_id', $template->preview_category_id);
        $previewArticleId  = $request->query('preview_article_id', $template->preview_article_id);

        $resolvedLayout = $template->layout_json ?? [];

        $allArticles = Article::publicados()
            ->select('id', 'title', 'slug', 'excerpt', 'body', 'featured_image', 'icon', 'order', 'category_id', 'author_id', 'published_at', 'reading_time', 'extra_fields_data')
            ->with(['category:id,name,slug,extra_fields_schema', 'author:id,name'])
            ->orderByDesc('published_at')
            ->take(50)
            ->get();

        $allCategories = Category::activas()
            ->select('id', 'name', 'slug', 'description', 'featured_image', 'icon', 'color', 'order', 'extra_fields_schema', 'category_builder_template_id', 'article_builder_template_id')
            ->orderBy('order', 'asc')
            ->get();

        [$article, $category] = $this->resolveSampleEntities(
            $template,
            $previewArticleId,
            $previewCategoryId,
            $allCategories,
            $allArticles
        );

        if ($template->type === 'single_article' && $article) {
            $resolvedLayout = $this->resolverService->resolveForArticle($resolvedLayout, $article);
        } elseif ($template->type === 'category_archive' && $category) {
            $resolvedLayout = $this->resolverService->resolveForCategory($resolvedLayout, $category);
        }

        return Inertia::render('Paginas/BuilderPreviewFrame', [
            'pagina'           => [
                'id'          => $template->id,
                'title'       => $template->name,
                'layout_json' => $resolvedLayout,
            ],
            'initialLayout'    => $resolvedLayout,
            'esPlantilla'      => true,
            'tipoPlantilla'    => $template->type,
            'categoriaMuestra' => $category ? (new CategoryResource($category))->resolve() : null,
            'articuloMuestra'  => $article ? (new ArticleResource($article))->resolve() : null,
            'articulosMuestra' => ArticleResource::collection($allArticles)->resolve(),
            'descargasMuestra' => PageBuilderResolverService::obtenerDescargasMuestraBuilder(),
            'categorias'       => CategoryResource::collection($allCategories)->resolve(),
        ]);
    }

    /**
     * Actualiza el diseño visual (`layout_json`) de la plantilla.
     */
    public function update(Request $request, BuilderTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'layout_json' => 'required|array',
        ]);

        $sanitizedLayout = $this->sanitizarLayout($validated['layout_json']);
        $sanitizedLayout = $this->normalizarElementosLayout($sanitizedLayout);

        $template->update([
            'layout_json' => $sanitizedLayout,
        ]);

        CacheInvalidationService::limpiarCachePublica();

        return back()->with('success', 'Diseño de la plantilla guardado correctamente.');
    }

    /**
     * Resuelve en memoria el layout enviado desde el editor para el previsualizador en vivo.
     */
    public function resolvePreview(Request $request, BuilderTemplate $template): JsonResponse
    {
        $layout = $request->input('layout_json', []);
        $previewCategoryId = $request->input('preview_category_id', $template->preview_category_id);
        $previewArticleId  = $request->input('preview_article_id', $template->preview_article_id);

        $allCategories = Category::activas()->get();
        $allArticles = Article::publicados()->take(20)->get();

        [$article, $category] = $this->resolveSampleEntities(
            $template,
            $previewArticleId,
            $previewCategoryId,
            $allCategories,
            $allArticles
        );

        if ($template->type === 'single_article' && $article) {
            $layout = $this->resolverService->resolveForArticle($layout, $article);
        } elseif ($template->type === 'category_archive' && $category) {
            $layout = $this->resolverService->resolveForCategory($layout, $category);
        }

        return response()->json([
            'layout' => $layout,
        ]);
    }

    /**
     * Resuelve la entidad de artículo y categoría de muestra para previsualización.
     *
     * @return array{0: ?Article, 1: ?Category}
     */
    private function resolveSampleEntities(
        BuilderTemplate $template,
        ?int $previewArticleId,
        ?int $previewCategoryId,
        $allCategories,
        $allArticles
    ): array {
        $article = null;
        $category = null;

        if ($template->type === 'single_article') {
            if ($previewArticleId) {
                $article = Article::with(['category:id,name,slug,extra_fields_schema,category_builder_template_id,article_builder_template_id', 'author:id,name'])->find($previewArticleId);
            }
            if (!$article) {
                // 1. Artículo con esta plantilla asignada directamente
                $article = $template->articles()->publicados()->with(['category:id,name,slug,extra_fields_schema,category_builder_template_id,article_builder_template_id', 'author:id,name'])->orderByDesc('published_at')->first();
            }
            if (!$article) {
                // 2. Artículo de alguna categoría que use esta plantilla como plantilla de artículos
                $categoryIds = $template->categoriesAsArticleTemplate()->pluck('id');
                if ($categoryIds->isNotEmpty()) {
                    $article = Article::whereIn('category_id', $categoryIds)
                        ->publicados()
                        ->with(['category:id,name,slug,extra_fields_schema,category_builder_template_id,article_builder_template_id', 'author:id,name'])
                        ->orderByDesc('published_at')
                        ->first();
                }
            }
            if (!$article && ($previewCategoryId || $template->preview_category_id)) {
                // 3. Artículo de la categoría de previsualización configurada
                $catId = $previewCategoryId ?: $template->preview_category_id;
                $article = Article::where('category_id', $catId)
                    ->publicados()
                    ->with(['category:id,name,slug,extra_fields_schema,category_builder_template_id,article_builder_template_id', 'author:id,name'])
                    ->orderByDesc('published_at')
                    ->first();
            }
            if (!$article) {
                // 4. Fallback: primer artículo publicado general
                $article = $allArticles->first();
            }

            $category = $article?->category
                ?? $template->categoriesAsArticleTemplate()->first()
                ?? ($previewCategoryId ? Category::find($previewCategoryId) : null)
                ?? ($template->preview_category_id ? Category::find($template->preview_category_id) : null)
                ?? $allCategories->first();
        } elseif ($template->type === 'category_archive') {
            if ($previewCategoryId) {
                $category = Category::find($previewCategoryId);
            }
            if (!$category) {
                $category = $template->categoriesAsCategoryTemplate()->first();
            }
            if (!$category && $template->preview_category_id) {
                $category = Category::find($template->preview_category_id);
            }
            if (!$category) {
                $category = $allCategories->first();
            }

            if ($category) {
                $article = Article::where('category_id', $category->id)
                    ->publicados()
                    ->with(['category:id,name,slug,extra_fields_schema,category_builder_template_id,article_builder_template_id', 'author:id,name'])
                    ->orderByDesc('published_at')
                    ->first();
            }
            if (!$article) {
                $article = $allArticles->first();
            }
        }

        return [$article, $category];
    }

    /**
     * Sanitiza recursivamente cadenas de texto y normaliza espacios en el árbol JSON.
     */
    private function sanitizarLayout(array $layout): array
    {
        array_walk_recursive($layout, function (&$item) {
            if (is_string($item)) {
                $item = str_replace(["&nbsp;", "\u{00A0}", "\xc2\xa0"], " ", $item);
                $item = preg_replace('/[ ]{2,}/', ' ', $item);
                $item = trim($item);
            }
        });

        return $layout;
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
     * Obtiene descargas publicadas de muestra sin acoplamiento rígido si el módulo no existe.
     */
    protected function obtenerDescargasMuestra(): array
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
