<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Servicio para gestionar categorías del CMS.
 */
class CategoryService
{
    /**
     * Listar categorías paginadas con conteo de artículos.
     */
    public function listar(int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('cms.paginacion.categorias_por_pagina', 20);

        return Category::with('parent:id,name')
            ->withCount('articles')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Listar categorías de la papelera.
     */
    public function listarPapelera(int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('cms.paginacion.categorias_por_pagina', 20);

        return Category::onlyTrashed()
            ->with('parent:id,name')
            ->withCount('articles')
            ->orderByDesc('deleted_at')
            ->paginate($perPage);
    }

    /**
     * Obtener una categoría por ID con relaciones.
     */
    public function obtener(int $id): Category
    {
        return Category::with('parent:id,name')
            ->withCount('articles')
            ->findOrFail($id);
    }

    /**
     * Crear una nueva categoría.
     */
    public function crear(array $datos): Category
    {
        if (empty($datos['slug'])) {
            $datos['slug'] = Str::slug($datos['name']);
        }

        return Category::create($datos);
    }

    /**
     * Actualizar una categoría existente.
     */
    public function actualizar(Category $category, array $datos): Category
    {
        $category->update($datos);

        return $category->fresh();
    }

    /**
     * Eliminar categoría (soft delete).
     */
    public function eliminar(Category $category): void
    {
        $category->delete();
    }

    /**
     * Restaurar categoría de la papelera.
     */
    public function restaurar(int $id): Category
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        return $category;
    }

    /**
     * Eliminar categoría definitivamente.
     */
    public function eliminarDefinitivo(int $id): void
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->forceDelete();
    }

    /**
     * Vaciar completamente la papelera de categorías.
     */
    public function vaciarPapelera(): void
    {
        Category::onlyTrashed()->forceDelete();
    }

    /**
     * Contar categorías en papelera.
     */
    public function conteoPapelera(): int
    {
        return Category::onlyTrashed()->count();
    }

    /**
     * Listar categorías activas para selectores (sin paginación).
     */
    public function listarParaSelector(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::activas()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'parent_id', 'extra_fields_schema']);
    }
}
