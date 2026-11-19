<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Services\ArticleService;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar artículos del CMS.
 */
class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly CategoryService $categoryService,
    ) {}

    /**
     * Listado paginado de artículos con filtros opcionales.
     */
    public function index(Request $request): Response
    {
        $filtros = $request->only(['status', 'category_id', 'search', 'sort_by', 'sort_dir']);

        return Inertia::render('Articulos/Index', [
            'articulos'       => ArticleResource::collection($this->articleService->listar($filtros)),
            'filtros'         => $filtros,
            'categorias'      => $this->categoryService->listarParaSelector(),
            'conteoPapelera'  => Inertia::lazy(fn () => $this->articleService->conteoPapelera()),
            'estadosArticulo' => config('cms.estados_articulo', []),
        ]);
    }

    /**
     * Papelera de artículos.
     */
    public function trashed(): Response
    {
        return Inertia::render('Articulos/Trashed', [
            'articulos' => ArticleResource::collection($this->articleService->listarPapelera()),
        ]);
    }

    /**
     * Formulario de creación con preservación de filtros de retorno.
     */
    public function create(Request $request): Response
    {
        $plantillasArticulo = \App\Models\BuilderTemplate::activas()->porTipo('single_article')->orderBy('name')->get();
        $filtrosRetorno = array_filter(
            $request->only(['status', 'category_id', 'search', 'sort_by', 'sort_dir', 'page']),
            fn($v) => !is_null($v) && $v !== '' && $v !== 'all'
        );

        return Inertia::render('Articulos/Create', [
            'categorias'         => $this->categoryService->listarParaSelector(),
            'estadosArticulo'    => config('cms.estados_articulo', []),
            'plantillasArticulo' => \App\Http\Resources\BuilderTemplateResource::collection($plantillasArticulo)->resolve(),
            'filtrosRetorno'     => $filtrosRetorno,
        ]);
    }

    /**
     * Guardar nuevo artículo y retornar al listado preservando filtros previos si existían.
     */
    public function store(StoreArticleRequest $request)
    {
        $datos = $request->validated();
        $datos['author_id'] = $request->user()->id;

        $this->articleService->crear($datos);

        $filtrosRetorno = $request->input('_filtros_retorno', $request->query());
        if (is_string($filtrosRetorno)) {
            $filtrosRetorno = json_decode($filtrosRetorno, true) ?? [];
        }
        $filtrosValidos = array_filter(
            array_intersect_key((array)$filtrosRetorno, array_flip([
                'status', 'category_id', 'search', 'sort_by', 'sort_dir', 'page'
            ])),
            fn($v) => !is_null($v) && $v !== '' && $v !== 'all'
        );

        return redirect()->route('articulos.index', $filtrosValidos)
            ->with('success', 'Artículo creado correctamente.');
    }

    /**
     * Formulario de edición con preservación de filtros de retorno.
     */
    public function edit(Request $request, Article $article): Response
    {
        $articleData = $this->articleService->obtener($article->id);
        $plantillasArticulo = \App\Models\BuilderTemplate::activas()->porTipo('single_article')->orderBy('name')->get();
        $filtrosRetorno = array_filter(
            $request->only(['status', 'category_id', 'search', 'sort_by', 'sort_dir', 'page']),
            fn($v) => !is_null($v) && $v !== '' && $v !== 'all'
        );

        return Inertia::render('Articulos/Edit', [
            'articulo'           => (new ArticleResource($articleData))->resolve(),
            'categorias'         => $this->categoryService->listarParaSelector(),
            'estadosArticulo'    => config('cms.estados_articulo', []),
            'plantillasArticulo' => \App\Http\Resources\BuilderTemplateResource::collection($plantillasArticulo)->resolve(),
            'filtrosRetorno'     => $filtrosRetorno,
        ]);
    }

    /**
     * Actualizar artículo y retornar al listado preservando filtros previos si existían.
     */
    public function update(UpdateArticleRequest $request, Article $article)
    {
        $this->articleService->actualizar($article, $request->validated());

        $filtrosRetorno = $request->input('_filtros_retorno', $request->query());
        if (is_string($filtrosRetorno)) {
            $filtrosRetorno = json_decode($filtrosRetorno, true) ?? [];
        }
        $filtrosValidos = array_filter(
            array_intersect_key((array)$filtrosRetorno, array_flip([
                'status', 'category_id', 'search', 'sort_by', 'sort_dir', 'page'
            ])),
            fn($v) => !is_null($v) && $v !== '' && $v !== 'all'
        );

        return redirect()->route('articulos.index', $filtrosValidos)
            ->with('success', 'Artículo actualizado correctamente.');
    }

    /**
     * Cambiar estado de un artículo directamente desde el listado.
     */
    public function changeStatus(Request $request, Article $article)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:draft,published,scheduled'],
        ]);

        $nuevoEstado = $request->input('status');
        $datos = ['status' => $nuevoEstado];

        // Si se cambia a publicado y no tenía fecha o la fecha era futura, asignar fecha actual para publicar de inmediato
        if ($nuevoEstado === 'published') {
            if (!$article->published_at || $article->published_at->isFuture()) {
                $datos['published_at'] = now();
            }
            if ($article->published_until && $article->published_until->isPast()) {
                $datos['published_until'] = null;
            }
        }

        $article->update($datos);

        return redirect()->back()
            ->with('success', "Estado del artículo \"{$article->title}\" actualizado correctamente.");
    }

    /**
     * Duplicar un artículo existente.
     */
    public function duplicate(Request $request, Article $article)
    {
        $this->articleService->duplicar($article, $request->user()?->id);

        return redirect()->route('articulos.index')
            ->with('success', "Artículo \"{$article->title}\" duplicado correctamente como despublicado.");
    }

    /**
     * Eliminar artículo (soft delete).
     */
    public function destroy(Article $article)
    {
        $this->articleService->eliminar($article);

        return redirect()->route('articulos.index')
            ->with('success', 'Artículo movido a la papelera.');
    }

    /**
     * Restaurar artículo desde la papelera.
     */
    public function restore(int $id)
    {
        $this->articleService->restaurar($id);

        return redirect()->route('articulos.trashed')
            ->with('success', 'Artículo restaurado correctamente.');
    }

    /**
     * Reordenar artículos (Drag and Drop).
     * Requiere filtro de categoría para evitar corrupción de orden entre categorías.
     * Usa page + per_page para calcular el offset correcto en paginación.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'ids'         => ['required', 'array'],
            'ids.*'       => ['integer', 'exists:articles,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'page'        => ['sometimes', 'integer', 'min:1'],
            'per_page'    => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 12);
        $offset  = ($page - 1) * $perPage;

        $this->articleService->reordenar(
            $request->input('ids'),
            (int) $request->input('category_id'),
            $offset
        );

        return redirect()->back()
            ->with('success', 'Orden de artículos actualizado correctamente.');
    }

    /**
     * Eliminar artículo definitivamente.
     */
    public function forceDelete(int $id)
    {
        $this->articleService->eliminarDefinitivo($id);

        return redirect()->route('articulos.trashed')
            ->with('success', 'Artículo eliminado definitivamente.');
    }

    /**
     * Vaciar la papelera de artículos por completo.
     */
    public function emptyTrash()
    {
        $this->articleService->vaciarPapelera();

        return redirect()->route('articulos.trashed')
            ->with('success', 'La papelera de artículos se ha vaciado por completo.');
    }
}
