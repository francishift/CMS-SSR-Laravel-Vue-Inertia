<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\CategoryService;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar menús y sus items.
 */
class MenuController extends Controller
{
    public function __construct(
        private readonly MenuService $service,
        private readonly CategoryService $categoryService,
    ) {}

    /**
     * Listado de menús.
     */
    public function index(): Response
    {
        return Inertia::render('Menus/Index', [
            'menus' => $this->service->listar()->map(fn ($menu) => [
                'id'          => $menu->id,
                'name'        => $menu->name,
                'slug'        => $menu->slug,
                'location'    => $menu->location,
                'is_active'   => $menu->is_active,
                'items_count' => $menu->items_count,
            ]),
            'conteoPapelera' => $this->service->conteoPapelera(),
        ]);
    }

    /**
     * Vista de la papelera de menús.
     */
    public function trashed(): Response
    {
        return Inertia::render('Menus/Trashed', [
            'menus' => $this->service->listarPapelera()->map(fn ($menu) => [
                'id'          => $menu->id,
                'name'        => $menu->name,
                'slug'        => $menu->slug,
                'location'    => $menu->location,
                'is_active'   => $menu->is_active,
                'items_count' => $menu->items_count,
                'deleted_at'  => $menu->deleted_at?->diffForHumans(),
            ]),
            'conteoPapelera' => $this->service->conteoPapelera(),
        ]);
    }

    /**
     * Restaurar un menú de la papelera.
     */
    public function restore(int $id)
    {
        $this->service->restaurar($id);

        return redirect()->route('menus.trashed')
            ->with('success', 'Menú restaurado correctamente.');
    }

    /**
     * Eliminar definitivamente un menú (force delete).
     */
    public function forceDelete(int $id)
    {
        $this->service->eliminarDefinitivo($id);

        return redirect()->route('menus.trashed')
            ->with('success', 'Menú eliminado de forma permanente.');
    }

    /**
     * Editor de un menú con sus items.
     */
    public function edit(Menu $menu): Response
    {
        $menuConItems = $this->service->obtenerConItems($menu->id);
        $trashedItems = MenuItem::onlyTrashed()->where('menu_id', $menu->id)->get();

        return Inertia::render('Menus/Edit', [
            'menu'       => [
                'id'        => $menuConItems->id,
                'name'      => $menuConItems->name,
                'slug'      => $menuConItems->slug,
                'location'  => $menuConItems->location,
                'is_active' => (bool) $menuConItems->is_active,
                'all_items' => $menuConItems->items->map(fn ($item) => [
                    'id'           => $item->id,
                    'menu_id'      => $item->menu_id,
                    'parent_id'    => $item->parent_id,
                    'title'        => $item->title,
                    'url'          => $item->url,
                    'route_name'   => $item->route_name,
                    'category_id'  => $item->category_id,
                    'article_id'   => $item->article_id,
                    'page_id'      => $item->page_id,
                    'resolved_url' => $item->resolved_url,
                    'target'       => $item->target,
                    'is_nofollow'  => (bool) $item->is_nofollow,
                    'icon'         => $item->icon,
                    'order'        => (int) $item->order,
                    'is_active'    => (bool) $item->is_active,
                ])->values()->toArray(),
                'trashed_items' => $trashedItems->map(fn ($item) => [
                    'id'         => $item->id,
                    'title'      => $item->title,
                    'url'        => $item->url,
                    'deleted_at' => $item->deleted_at?->diffForHumans(),
                ])->values()->toArray(),
            ],
            'categorias' => $this->categoryService->listarParaSelector(),
            'articulos'  => \App\Models\Article::publicados()
                ->select('id', 'title', 'slug', 'category_id', 'status', 'published_at')
                ->with('category:id,slug,name')
                ->orderBy('title')
                ->get(),
            'paginas'    => \App\Models\Page::activas()
                ->select('id', 'title', 'slug')
                ->orderBy('title')
                ->get(),
        ]);
    }

    /**
     * Crear un nuevo menú.
     */
    public function store(StoreMenuRequest $request)
    {
        $this->service->crear($request->validated());

        return redirect()->route('menus.index')
            ->with('success', 'Menú creado correctamente.');
    }

    /**
     * Actualizar datos del menú (no los items).
     */
    public function update(StoreMenuRequest $request, Menu $menu)
    {
        $this->service->actualizar($menu, $request->validated());

        return redirect()->route('menus.edit', $menu)
            ->with('success', 'Menú actualizado correctamente.');
    }

    /**
     * Eliminar un menú y todos sus items.
     */
    public function destroy(Menu $menu)
    {
        $this->service->eliminar($menu);

        return redirect()->route('menus.index')
            ->with('success', 'Menú eliminado correctamente.');
    }

    // ─── Gestión de Items ───────────────────────────────

    /**
     * Crear un item en un menú.
     */
    public function storeItem(StoreMenuItemRequest $request, Menu $menu)
    {
        $this->service->crearItem($menu, $request->validated());

        return redirect()->route('menus.edit', $menu)
            ->with('success', 'Item añadido correctamente.');
    }

    /**
     * Actualizar un item de menú.
     */
    public function updateItem(StoreMenuItemRequest $request, MenuItem $item)
    {
        $this->service->actualizarItem($item, $request->validated());

        return redirect()->route('menus.edit', $item->menu_id)
            ->with('success', 'Item actualizado correctamente.');
    }

    /**
     * Eliminar un item de menú (soft delete).
     */
    public function destroyItem(MenuItem $item)
    {
        $menuId = $item->menu_id;
        $this->service->eliminarItem($item);

        return redirect()->route('menus.edit', $menuId)
            ->with('success', 'Item eliminado correctamente.');
    }

    /**
     * Restaurar un item de menú de la papelera.
     */
    public function restoreItem(int $id)
    {
        $item = $this->service->restaurarItem($id);

        return redirect()->route('menus.edit', $item->menu_id)
            ->with('success', 'Elemento de menú restaurado correctamente.');
    }

    /**
     * Eliminar definitivamente un item de menú.
     */
    public function forceDeleteItem(int $id)
    {
        $item = MenuItem::onlyTrashed()->findOrFail($id);
        $menuId = $item->menu_id;
        $this->service->eliminarDefinitivoItem($id);

        return redirect()->route('menus.edit', $menuId)
            ->with('success', 'Elemento de menú eliminado de forma permanente.');
    }

    /**
     * Reordenar items de un menú (via AJAX).
     */
    public function reorderItems(Request $request, Menu $menu)
    {
        $request->validate([
            'items'            => ['required', 'array'],
            'items.*.id'       => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.order'    => ['required', 'integer', 'min:0'],
            'items.*.parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
        ]);

        $this->service->reordenarItems($request->input('items'));

        return back()->with('success', 'Orden actualizado.');
    }
}
