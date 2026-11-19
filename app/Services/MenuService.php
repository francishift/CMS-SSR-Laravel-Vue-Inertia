<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Servicio para gestionar menús y sus items.
 */
class MenuService
{
    /**
     * Listar todos los menús con conteo de items.
     */
    public function listar()
    {
        return Menu::withCount('items')
            ->orderBy('location')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtener un menú con todos sus items ordenados y anidados.
     */
    public function obtenerConItems(int $id): Menu
    {
        return Menu::with([
            'items' => fn ($q) => $q->orderBy('order'),
            'items.category:id,name,slug',
            'items.article:id,title,slug,category_id',
            'items.article.category:id,slug',
            'items.page:id,title,slug',
            'items.children' => fn ($q) => $q->orderBy('order'),
        ])->findOrFail($id);
    }

    /**
     * Crear un nuevo menú.
     */
    public function crear(array $datos): Menu
    {
        $datos['slug'] = $datos['slug'] ?? Str::slug($datos['name']);
        $menu = Menu::create($datos);
        $this->invalidarCache();

        return $menu;
    }

    /**
     * Actualizar un menú existente.
     */
    public function actualizar(Menu $menu, array $datos): Menu
    {
        $menu->update($datos);
        $this->invalidarCache();

        return $menu->fresh();
    }

    /**
     * Eliminar un menú (soft delete).
     */
    public function eliminar(Menu $menu): void
    {
        $menu->delete();
        $this->invalidarCache();
    }

    /**
     * Listar menús en la papelera.
     */
    public function listarPapelera()
    {
        return Menu::onlyTrashed()
            ->withCount('items')
            ->orderByDesc('deleted_at')
            ->get();
    }

    /**
     * Restaurar un menú de la papelera.
     */
    public function restaurar(int $id): Menu
    {
        $menu = Menu::onlyTrashed()->findOrFail($id);
        $menu->restore();
        $this->invalidarCache();

        return $menu;
    }

    /**
     * Eliminar menú definitivamente (force delete).
     */
    public function eliminarDefinitivo(int $id): void
    {
        $menu = Menu::onlyTrashed()->findOrFail($id);
        $menu->forceDelete();
        $this->invalidarCache();
    }

    /**
     * Conteo de menús en papelera.
     */
    public function conteoPapelera(): int
    {
        return Menu::onlyTrashed()->count();
    }

    // ─── Gestión de Items ───────────────────────────────

    /**
     * Crear un item en un menú.
     */
    public function crearItem(Menu $menu, array $datos): MenuItem
    {
        $datos['menu_id'] = $menu->id;
        $this->sanitizarDestinosItem($datos);

        // Asignar el orden más alto si no se especifica
        if (!isset($datos['order'])) {
            $maxOrder = $menu->items()
                ->where('parent_id', $datos['parent_id'] ?? null)
                ->max('order');

            $datos['order'] = $maxOrder !== null ? (int) $maxOrder + 1 : 0;
        }

        $item = MenuItem::create($datos);
        $this->invalidarCache();

        return $item;
    }

    /**
     * Actualizar un item de menú.
     */
    public function actualizarItem(MenuItem $item, array $datos): MenuItem
    {
        $this->sanitizarDestinosItem($datos);

        $item->update($datos);
        $this->invalidarCache();

        return $item->fresh();
    }

    /**
     * Garantizar que los campos de destino de un item sean mutuamente excluyentes.
     */
    protected function sanitizarDestinosItem(array &$datos): void
    {
        if (!empty($datos['page_id'])) {
            $datos['category_id'] = null;
            $datos['article_id'] = null;
            $datos['url'] = null;
            $datos['route_name'] = null;
        } elseif (!empty($datos['category_id'])) {
            $datos['page_id'] = null;
            $datos['article_id'] = null;
            $datos['url'] = null;
            $datos['route_name'] = null;
        } elseif (!empty($datos['article_id'])) {
            $datos['category_id'] = null;
            $datos['page_id'] = null;
            $datos['url'] = null;
            $datos['route_name'] = null;
        } elseif (!empty($datos['route_name'])) {
            $datos['category_id'] = null;
            $datos['page_id'] = null;
            $datos['article_id'] = null;
            $datos['url'] = null;
        } elseif (isset($datos['url']) && $datos['url'] === '/') {
            $datos['category_id'] = null;
            $datos['page_id'] = null;
            $datos['article_id'] = null;
            $datos['route_name'] = null;
        } elseif (!empty($datos['url'])) {
            $datos['category_id'] = null;
            $datos['page_id'] = null;
            $datos['article_id'] = null;
            $datos['route_name'] = null;
        }
    }

    /**
     * Eliminar un item de menú (soft delete).
     */
    public function eliminarItem(MenuItem $item): void
    {
        $item->delete();
        $this->invalidarCache();
    }

    /**
     * Restaurar un item de menú de la papelera.
     */
    public function restaurarItem(int $id): MenuItem
    {
        $item = MenuItem::onlyTrashed()->findOrFail($id);
        $item->restore();
        $this->invalidarCache();

        return $item;
    }

    /**
     * Eliminar definitivamente un item de menú.
     */
    public function eliminarDefinitivoItem(int $id): void
    {
        $item = MenuItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();
        $this->invalidarCache();
    }

    /**
     * Reordenar items de un menú.
     *
     * @param  array  $orden  Array de objetos [{id, order, parent_id}]
     */
    public function reordenarItems(array $orden): void
    {
        foreach ($orden as $item) {
            MenuItem::where('id', $item['id'])->update([
                'order'     => $item['order'],
                'parent_id' => $item['parent_id'] ?? null,
            ]);
        }
        $this->invalidarCache();
    }

    /**
     * Invalida de inmediato toda la caché de menús.
     */
    public function invalidarCache(): void
    {
        if (Cache::supportsTags()) {
            try {
                Cache::tags(['menus'])->flush();
            } catch (\Throwable) {}
        }

        Cache::forget('menu_header');
        Cache::forget('menu_footer');
        Cache::forget('menu_sidebar');
        Cache::forget('menus_activos_con_items');
    }
}
