<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar categorías del CMS.
 */
class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $service,
    ) {}

    /**
     * Listado paginado de categorías.
     */
    public function index(): Response
    {
        return Inertia::render('Categorias/Index', [
            'categorias'     => CategoryResource::collection($this->service->listar()),
            'conteoPapelera' => Inertia::lazy(fn () => $this->service->conteoPapelera()),
        ]);
    }

    /**
     * Papelera de categorías.
     */
    public function trashed(): Response
    {
        return Inertia::render('Categorias/Trashed', [
            'categorias' => CategoryResource::collection($this->service->listarPapelera()),
        ]);
    }

    /**
     * Obtener tipos de campo extra formateados.
     */
    private function obtenerTiposCampoExtra(): array
    {
        return collect(config('cms.tipos_campo_extra', []))->map(fn ($nombre, $clave) => [
            'clave'  => $clave,
            'nombre' => $nombre,
        ])->values()->toArray();
    }

    /**
     * Formulario de creación.
     */
    public function create(): Response
    {
        $plantillasCategoria = \App\Models\BuilderTemplate::activas()->porTipo('category_archive')->orderBy('name')->get();
        $plantillasArticulo  = \App\Models\BuilderTemplate::activas()->porTipo('single_article')->orderBy('name')->get();

        return Inertia::render('Categorias/Create', [
            'categoriasParaSelector' => $this->service->listarParaSelector(),
            'plantillasDisponibles'  => config('cms.plantillas', []),
            'plantillasCategoria'    => \App\Http\Resources\BuilderTemplateResource::collection($plantillasCategoria)->resolve(),
            'plantillasArticulo'     => \App\Http\Resources\BuilderTemplateResource::collection($plantillasArticulo)->resolve(),
            'tiposCampoExtra'        => $this->obtenerTiposCampoExtra(),
        ]);
    }

    /**
     * Guardar nueva categoría.
     */
    public function store(StoreCategoryRequest $request)
    {
        $this->service->crear($request->validated());

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    /**
     * Formulario de edición.
     */
    public function edit(Category $category): Response
    {
        $categoryData = $this->service->obtener($category->id);
        $plantillasCategoria = \App\Models\BuilderTemplate::activas()->porTipo('category_archive')->orderBy('name')->get();
        $plantillasArticulo  = \App\Models\BuilderTemplate::activas()->porTipo('single_article')->orderBy('name')->get();

        return Inertia::render('Categorias/Edit', [
            'categoria'              => (new CategoryResource($categoryData))->resolve(),
            'categoriasParaSelector' => $this->service->listarParaSelector(),
            'plantillasDisponibles'  => config('cms.plantillas', []),
            'plantillasCategoria'    => \App\Http\Resources\BuilderTemplateResource::collection($plantillasCategoria)->resolve(),
            'plantillasArticulo'     => \App\Http\Resources\BuilderTemplateResource::collection($plantillasArticulo)->resolve(),
            'tiposCampoExtra'        => $this->obtenerTiposCampoExtra(),
        ]);
    }

    /**
     * Actualizar categoría.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->service->actualizar($category, $request->validated());

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    /**
     * Eliminar categoría (soft delete).
     */
    public function destroy(Category $category)
    {
        $this->service->eliminar($category);

        return redirect()->route('categorias.index')
            ->with('success', 'Categoría movida a la papelera.');
    }

    /**
     * Restaurar categoría desde la papelera.
     */
    public function restore(int $id)
    {
        $this->service->restaurar($id);

        return redirect()->route('categorias.trashed')
            ->with('success', 'Categoría restaurada correctamente.');
    }

    /**
     * Eliminar categoría definitivamente.
     */
    public function forceDelete(int $id)
    {
        $this->service->eliminarDefinitivo($id);

        return redirect()->route('categorias.trashed')
            ->with('success', 'Categoría eliminada definitivamente.');
    }

    /**
     * Vaciar la papelera de categorías por completo.
     */
    public function emptyTrash()
    {
        $this->service->vaciarPapelera();

        return redirect()->route('categorias.trashed')
            ->with('success', 'La papelera de categorías se ha vaciado por completo.');
    }
}
