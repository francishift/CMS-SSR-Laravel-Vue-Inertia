<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Services\PageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión de Páginas Estáticas en el panel privado.
 */
class PageController extends Controller
{
    public function __construct(
        private readonly PageService $service,
    ) {}

    /**
     * Listado de páginas.
     */
    public function index(Request $request): Response
    {
        $filtros = $request->only(['search', 'is_active']);
        $paginas = $this->service->listar($filtros);

        return Inertia::render('Paginas/Index', [
            'paginas'        => PageResource::collection($paginas),
            'filtros'        => $filtros,
            'conteoPapelera' => $this->service->conteoPapelera(),
        ]);
    }

    /**
     * Formulario de creación de página.
     */
    public function create(): Response
    {
        return Inertia::render('Paginas/Create');
    }

    /**
     * Guardar una nueva página.
     */
    public function store(StorePageRequest $request)
    {
        $this->service->crear($request->validated());

        return redirect()->route('paginas.index')
            ->with('success', 'Página estática creada correctamente.');
    }

    /**
     * Formulario de edición de página.
     */
    public function edit(Page $page): Response
    {
        return Inertia::render('Paginas/Edit', [
            'pagina' => new PageResource($page),
        ]);
    }

    /**
     * Actualizar una página.
     */
    public function update(UpdatePageRequest $request, Page $page)
    {
        $this->service->actualizar($page, $request->validated());

        return redirect()->route('paginas.index')
            ->with('success', 'Página estática actualizada correctamente.');
    }

    /**
     * Cambiar estado activo/inactivo.
     */
    public function toggleActive(Page $page)
    {
        $this->service->toggleActive($page);

        return back()->with('success', 'Estado de la página actualizado.');
    }

    /**
     * Designar una página como portada del sitio.
     */
    public function setHomepage(Page $page)
    {
        $this->service->marcarComoHomepage($page);

        return back()->with('success', "La página \"{$page->title}\" es ahora la página de inicio.");
    }

    /**
     * Mover a la papelera.
     */
    public function destroy(Page $page)
    {
        $this->service->eliminar($page);

        return redirect()->route('paginas.index')
            ->with('success', 'Página movida a la papelera.');
    }

    /**
     * Listar páginas en la papelera.
     */
    public function trashed(): Response
    {
        return Inertia::render('Paginas/Trashed', [
            'paginas' => PageResource::collection($this->service->listarPapelera()),
        ]);
    }

    /**
     * Restaurar una página de la papelera.
     */
    public function restore(int $id)
    {
        $this->service->restaurar($id);

        return redirect()->route('paginas.trashed')
            ->with('success', 'Página restaurada correctamente.');
    }

    /**
     * Eliminar definitivamente.
     */
    public function forceDelete(int $id)
    {
        $this->service->eliminarDefinitivo($id);

        return redirect()->route('paginas.trashed')
            ->with('success', 'Página eliminada definitivamente.');
    }
}
