<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Contrato puente desacoplado para resolver slugs dinámicos del catálogo de descargas
 * sin acoplar el controlador público principal del CMS a clases internas del módulo.
 */
interface DownloadSlugHandlerInterface
{
    /**
     * Comprueba si el slug raíz coincide con el catálogo configurado de descargas.
     */
    public function matchesCatalogSlug(string $slug): bool;

    /**
     * Renderiza la portada o página paginada del catálogo de descargas.
     */
    public function renderCatalog(Request $request, int $pageNumber = 1): Response|RedirectResponse;

    /**
     * Renderiza la ficha individual de un elemento descargable.
     */
    public function renderItem(Request $request, string $itemSlug): Response|RedirectResponse;
}
