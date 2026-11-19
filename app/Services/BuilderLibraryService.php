<?php

namespace App\Services;

use App\Models\BuilderLibraryItem;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio de Negocio para la Biblioteca de Elementos Guardados (Clean Architecture & SOLID).
 */
class BuilderLibraryService
{
    /**
     * Obtener listado de elementos guardados con filtros.
     */
    public function listar(?string $type = null, ?string $search = null): Collection
    {
        return BuilderLibraryItem::with('createdBy:id,name')
            ->porTipo($type)
            ->buscar($search)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Guardar un nuevo elemento o preset en la biblioteca.
     */
    public function crear(array $datos, ?int $userId = null): BuilderLibraryItem
    {
        if ($userId) {
            $datos['created_by'] = $userId;
        }

        return BuilderLibraryItem::create($datos);
    }

    /**
     * Actualizar datos de un preset.
     */
    public function actualizar(BuilderLibraryItem $item, array $datos): bool
    {
        return $item->update($datos);
    }

    /**
     * Eliminar un preset de la biblioteca.
     */
    public function eliminar(BuilderLibraryItem $item): bool
    {
        return $item->delete();
    }
}
