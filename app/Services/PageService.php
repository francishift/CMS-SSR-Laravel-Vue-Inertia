<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Servicio para la gestión de páginas estáticas del CMS.
 */
class PageService
{
    public function __construct(
        private readonly HtmlSanitizerService $sanitizer = new HtmlSanitizerService(),
    ) {}

    /**
     * Listar páginas paginadas con filtros opcionales.
     */
    public function listar(array $filtros = [], int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('cms.paginacion.articulos_por_pagina', 12);

        $query = Page::orderBy('order')->orderBy('title');

        if (!empty($filtros['search'])) {
            $query->where('title', 'like', "%{$filtros['search']}%")
                ->orWhere('slug', 'like', "%{$filtros['search']}%");
        }

        if (isset($filtros['is_active'])) {
            $query->where('is_active', (bool) $filtros['is_active']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Listar páginas de la papelera.
     */
    public function listarPapelera(int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('cms.paginacion.articulos_por_pagina', 12);

        return Page::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->paginate($perPage);
    }

    /**
     * Obtener una página por su ID.
     */
    public function obtener(int $id): Page
    {
        return Page::findOrFail($id);
    }

    /**
     * Obtener una página activa por su slug (para la web pública).
     */
    public function obtenerActivaPorSlug(string $slug): Page
    {
        return Page::activas()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Crear una nueva página.
     */
    public function crear(array $datos): Page
    {
        $this->sanitizarTexto($datos);

        if (empty($datos['slug'])) {
            $datos['slug'] = Str::slug($datos['title']);
        }

        if (isset($datos['body'])) {
            $datos['body'] = $this->sanitizer->sanitize($datos['body']);
        }

        return Page::create($datos);
    }

    /**
     * Actualizar una página existente y eliminar de disco cualquier imagen removida.
     */
    public function actualizar(Page $page, array $datos): Page
    {
        $this->sanitizarTexto($datos);

        if (empty($datos['slug'])) {
            $datos['slug'] = Str::slug($datos['title']);
        }

        if (isset($datos['body'])) {
            $datos['body'] = $this->sanitizer->sanitize($datos['body']);
            
            // Limpiar de disco imágenes que estaban en el cuerpo anterior pero ya no existen en el nuevo
            $this->limpiarImagenesEliminadas($page->body, $datos['body']);
        }

        $page->update($datos);

        return $page->fresh();
    }

    /**
     * Cambiar el estado activo/inactivo de una página.
     * No permite desactivar la página de inicio.
     */
    public function toggleActive(Page $page): Page
    {
        if ($page->is_homepage && $page->is_active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'is_active' => 'No se puede desactivar la página de inicio. Asigna otra página como inicio primero.',
            ]);
        }

        $page->update(['is_active' => !$page->is_active]);

        return $page;
    }

    /**
     * Designar una página como portada del sitio.
     *
     * Usa transacción atómica para garantizar la exclusión mutua:
     * primero desmarca la homepage actual, luego marca la nueva.
     * Si la página no está activa, se activa automáticamente.
     */
    public function marcarComoHomepage(Page $page): Page
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($page) {
            // Desmarcar la homepage actual (si existe)
            Page::where('is_homepage', true)
                ->where('id', '!=', $page->id)
                ->update(['is_homepage' => false]);

            // Marcar la nueva homepage y asegurar que esté activa
            $page->update([
                'is_homepage' => true,
                'is_active'   => true,
            ]);

            return $page->fresh();
        });
    }

    /**
     * Mover a la papelera (soft delete).
     * No permite eliminar la página de inicio.
     */
    public function eliminar(Page $page): void
    {
        if ($page->is_homepage) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'page' => 'No se puede eliminar la página de inicio. Asigna otra página como inicio primero.',
            ]);
        }

        $page->delete();
    }

    /**
     * Restaurar desde la papelera.
     */
    public function restaurar(int $id): Page
    {
        $page = Page::onlyTrashed()->findOrFail($id);
        $page->restore();

        return $page;
    }

    /**
     * Eliminar definitivamente y borrar todas las imágenes asociadas en disco.
     */
    public function eliminarDefinitivo(int $id): void
    {
        $page = Page::onlyTrashed()->findOrFail($id);
        
        // Borrar todas las imágenes de disco pertenecientes a esta página
        $this->limpiarImagenesEliminadas($page->body, '');

        $page->forceDelete();
    }

    /**
     * Contar páginas en papelera.
     */
    public function conteoPapelera(): int
    {
        return Page::onlyTrashed()->count();
    }

    // ─── Helpers de Limpieza de Disco ───────────────────

    /**
     * Extraer URLs de imágenes /storage/media/ de un fragmento de HTML.
     */
    private function extraerImagenesMedia(?string $html): array
    {
        if (empty($html)) {
            return [];
        }

        preg_match_all('/src=["\']([^"\']*storage\/media\/[^"\']+)["\']/i', $html, $coincidencias);

        return array_unique($coincidencias[1] ?? []);
    }

    /**
     * Eliminar de disco las imágenes presentes en el HTML antiguo que ya no figuran en el HTML nuevo.
     */
    private function limpiarImagenesEliminadas(?string $htmlAntiguo, ?string $htmlNuevo): void
    {
        $viejas = $this->extraerImagenesMedia($htmlAntiguo);
        $nuevas = $this->extraerImagenesMedia($htmlNuevo);

        $borradas = array_diff($viejas, $nuevas);

        foreach ($borradas as $url) {
            $filename = basename(parse_url($url, PHP_URL_PATH));
            if (!empty($filename) && Storage::disk('public')->exists('media/' . $filename)) {
                Storage::disk('public')->delete('media/' . $filename);
            }
        }
    }

    /**
     * Sanitizar campos de texto para evitar entidades &nbsp; innecesarias y soft hyphens indeseados.
     */
    protected function sanitizarTexto(array &$datos): void
    {
        if (isset($datos['body']) && is_string($datos['body'])) {
            $datos['body'] = str_replace(['&nbsp;', "\xC2\xAD", '&shy;'], [' ', '', ''], $datos['body']);
        }
        if (isset($datos['title']) && is_string($datos['title'])) {
            $datos['title'] = str_replace(['&nbsp;', "\xC2\xAD", '&shy;'], [' ', '', ''], $datos['title']);
        }
    }
}
