<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Servicio para gestionar artículos del CMS.
 */
class ArticleService
{
    /**
     * Listar artículos paginados con filtros opcionales.
     */
    public function listar(array $filtros = [], ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('cms.paginacion.articulos_por_pagina', 12);

        $query = Article::with(['category:id,name,slug,articles_order', 'author:id,name']);

        // Filtro por estado
        if (!empty($filtros['status'])) {
            if ($filtros['status'] === 'scheduled') {
                $query->where(function ($q) {
                    $q->where('status', 'scheduled')
                      ->orWhere(function ($sub) {
                          $sub->where('status', 'published')
                              ->whereNotNull('published_at')
                              ->where('published_at', '>', now());
                      });
                });
            } elseif ($filtros['status'] === 'published') {
                $query->whereIn('status', ['published', 'scheduled'])
                      ->whereNotNull('published_at')
                      ->where('published_at', '<=', now())
                      ->where(function ($q) {
                          $q->whereNull('published_until')
                            ->orWhere('published_until', '>=', now());
                      });
            } else {
                $query->where('status', $filtros['status']);
            }
        }

        // Filtro por categoría
        if (!empty($filtros['category_id'])) {
            $query->where('category_id', $filtros['category_id']);
        }

        // Ordenación de la consulta:
        // Si el usuario especifica un orden visual en el panel (sort_by), se respeta para la vista administrativa
        // sin alterar la columna 'order' ni interferir con el frontend.
        $sortBy = $filtros['sort_by'] ?? null;
        $sortDir = strtolower($filtros['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $columnasPermitidas = ['title', 'category', 'status', 'published_at', 'author'];

        if ($sortBy && in_array($sortBy, $columnasPermitidas, true)) {
            match ($sortBy) {
                'category' => $query->orderBy(
                    \App\Models\Category::select('name')->whereColumn('categories.id', 'articles.category_id'),
                    $sortDir
                ),
                'author' => $query->orderBy(
                    \App\Models\User::select('name')->whereColumn('users.id', 'articles.author_id'),
                    $sortDir
                ),
                default => $query->orderBy("articles.{$sortBy}", $sortDir),
            };
            $query->orderByDesc('articles.id');
        } else {
            // Sin ordenación explícita por columna:
            if (!empty($filtros['category_id'])) {
                // Al filtrar por una categoría concreta y sin ordenación por columna,
                // se muestra por orden manual para habilitar el Drag & Drop coherente.
                $query->orderBy('order', 'asc')->orderByDesc('published_at')->orderByDesc('id');
            } else {
                // Sin filtro de categoría: ordenar por fecha de publicación (más recientes primero)
                $query->orderByDesc('published_at')->orderByDesc('id');
            }
        }

        // Búsqueda por título
        if (!empty($filtros['search'])) {
            $query->where('title', 'like', "%{$filtros['search']}%");
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Listar artículos de la papelera.
     */
    public function listarPapelera(?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('cms.paginacion.articulos_por_pagina', 12);

        return Article::onlyTrashed()
            ->with(['category:id,name,slug', 'author:id,name'])
            ->orderByDesc('deleted_at')
            ->paginate($perPage);
    }

    /**
     * Obtener un artículo con todas sus relaciones.
     */
    public function obtener(int $id): Article
    {
        return Article::with(['category:id,name,slug,extra_fields_schema', 'author:id,name'])
            ->findOrFail($id);
    }

    /**
     * Crear un nuevo artículo.
     */
    public function crear(array $datos): Article
    {
        $this->sanitizarDatos($datos);

        if (empty($datos['slug'])) {
            $datos['slug'] = Str::slug($datos['title']);
        }

        // Asignar fecha de publicación actual por defecto si viene vacía
        if (empty($datos['published_at'])) {
            $datos['published_at'] = now();
        }

        return Article::create($datos);
    }

    /**
     * Actualizar un artículo existente.
     */
    public function actualizar(Article $article, array $datos): Article
    {
        $this->sanitizarDatos($datos);

        $fotoAntigua = $article->featured_image;
        $bodyAntiguo = $article->body;

        // Si cambia a publicado y no tiene fecha, asignarla
        if (($datos['status'] ?? $article->status) === 'published'
            && empty($datos['published_at'])
            && !$article->published_at) {
            $datos['published_at'] = now();
        }

        $article->update($datos);

        // Limpiar foto destacada antigua si se ha reemplazado
        if (!empty($fotoAntigua) && isset($datos['featured_image']) && $datos['featured_image'] !== $fotoAntigua) {
            MediaCleanupService::eliminarArchivoSiHuérfano($fotoAntigua);
        }

        // Limpiar imágenes internas removidas del body
        if (isset($datos['body']) && $datos['body'] !== $bodyAntiguo) {
            $viejas = MediaCleanupService::extraerUrlsImagenes($bodyAntiguo);
            $nuevas = MediaCleanupService::extraerUrlsImagenes($datos['body']);
            $removidas = array_diff($viejas, $nuevas);
            foreach ($removidas as $url) {
                MediaCleanupService::eliminarArchivoSiHuérfano($url);
            }
        }

        return $article->fresh();
    }

    /**
     * Duplicar un artículo existente como borrador.
     */
    public function duplicar(Article $article, ?int $authorId = null): Article
    {
        $copia = $article->replicate([
            'slug',
            'views_count',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $copia->title = $article->title . ' (Copia)';
        $copia->slug = Str::slug($article->title . '-copia-' . Str::random(5));
        $copia->status = 'draft';
        $copia->category_id = $article->category_id; // Copiar la categoría obligatoriamente
        $copia->published_at = now(); // Fecha actual por defecto al duplicar
        if ($authorId) {
            $copia->author_id = $authorId;
        }

        $copia->save();

        return $copia;
    }

    /**
     * Eliminar artículo (soft delete).
     */
    public function eliminar(Article $article): void
    {
        $article->delete();
    }

    /**
     * Restaurar artículo de la papelera.
     */
    public function restaurar(int $id): Article
    {
        $article = Article::onlyTrashed()->findOrFail($id);
        $article->restore();

        return $article;
    }

    /**
     * Eliminar artículo definitivamente y borrar sus archivos de imagen físicos.
     */
    public function eliminarDefinitivo(int $id): void
    {
        $article = Article::onlyTrashed()->findOrFail($id);

        $foto = $article->featured_image;
        $urlsInline = MediaCleanupService::extraerUrlsImagenes($article->body);

        $article->forceDelete();

        // Eliminar archivos físicos huérfanos del disco
        if ($foto) {
            MediaCleanupService::eliminarArchivoSiHuérfano($foto);
        }
        foreach ($urlsInline as $url) {
            MediaCleanupService::eliminarArchivoSiHuérfano($url);
        }
    }

    /**
     * Vaciar completamente la papelera de artículos y borrar sus imágenes asociadas.
     */
    public function vaciarPapelera(): void
    {
        $articulos = Article::onlyTrashed()->get();

        foreach ($articulos as $article) {
            $foto = $article->featured_image;
            $urlsInline = MediaCleanupService::extraerUrlsImagenes($article->body);

            $article->forceDelete();

            if ($foto) {
                MediaCleanupService::eliminarArchivoSiHuérfano($foto);
            }
            foreach ($urlsInline as $url) {
                MediaCleanupService::eliminarArchivoSiHuérfano($url);
            }
        }
    }

    /**
     * Contar artículos en papelera.
     */
    public function conteoPapelera(): int
    {
        return Article::onlyTrashed()->count();
    }

    // ─── Queries Públicas (para la web pública) ─────────

    /**
     * Obtener artículos publicados de una categoría (paginados con ordenación configurable).
     */
    public function publicadosPorCategoria($category, ?int $perPage = null, ?int $pageNumber = null): LengthAwarePaginator
    {
        $categoryModel = is_numeric($category)
            ? \App\Models\Category::find($category)
            : $category;

        $perPage = $perPage ?? ($categoryModel->per_page ?? config('cms.paginacion.articulos_por_pagina', 12));
        $orderType = $categoryModel->articles_order ?? 'published_at_desc';

        $query = Article::publicados()
            ->where('category_id', $categoryModel->id)
            ->with(['category', 'author:id,name']);

        switch ($orderType) {
            case 'published_at_asc':
                $query->orderBy('published_at', 'asc')->orderBy('id', 'asc');
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'manual':
                $query->orderBy('order', 'asc')->orderByDesc('id');
                break;
            case 'published_at_desc':
            default:
                $query->orderByDesc('published_at')->orderByDesc('id');
                break;
        }

        $currentPage = $pageNumber ?? (int) (request()->route('page') ?? request()->input('page', 1));
        if ($currentPage < 1) {
            $currentPage = 1;
        }

        $paginador = $query->paginate($perPage, ['*'], 'page', $currentPage);
        $paginador->withPath(url('/' . ltrim($categoryModel->slug, '/')));

        return $paginador;
    }

    /**
     * Obtener la navegación de artículo anterior y siguiente según el criterio de orden de la categoría.
     *
     * Usa 2 queries SQL con LIMIT 1 en vez de cargar todos los artículos de la categoría a memoria.
     * Esto es O(1) en vez de O(n), crítico para categorías con cientos de artículos.
     */
    public function obtenerNavegacionAnteriorYSiguiente(Article $article, \App\Models\Category $category): array
    {
        $orderType = $category->articles_order ?? 'published_at_desc';

        $baseQuery = Article::publicados()
            ->where('category_id', $category->id)
            ->select(['id', 'title', 'slug', 'featured_image']);

        // Construir queries de anterior y siguiente según el tipo de ordenación
        [$anterior, $siguiente] = match ($orderType) {
            'published_at_asc' => [
                (clone $baseQuery)
                    ->where(fn ($q) => $q
                        ->where('published_at', '<', $article->published_at)
                        ->orWhere(fn ($q2) => $q2
                            ->where('published_at', '=', $article->published_at)
                            ->where('id', '<', $article->id)))
                    ->orderByDesc('published_at')->orderByDesc('id')
                    ->first(),
                (clone $baseQuery)
                    ->where(fn ($q) => $q
                        ->where('published_at', '>', $article->published_at)
                        ->orWhere(fn ($q2) => $q2
                            ->where('published_at', '=', $article->published_at)
                            ->where('id', '>', $article->id)))
                    ->orderBy('published_at')->orderBy('id')
                    ->first(),
            ],
            'title_asc' => [
                (clone $baseQuery)->where('title', '<', $article->title)->orderByDesc('title')->first(),
                (clone $baseQuery)->where('title', '>', $article->title)->orderBy('title')->first(),
            ],
            'title_desc' => [
                (clone $baseQuery)->where('title', '>', $article->title)->orderBy('title')->first(),
                (clone $baseQuery)->where('title', '<', $article->title)->orderByDesc('title')->first(),
            ],
            'manual' => [
                (clone $baseQuery)
                    ->where(fn ($q) => $q
                        ->where('order', '<', $article->order)
                        ->orWhere(fn ($q2) => $q2
                            ->where('order', '=', $article->order)
                            ->where('id', '>', $article->id)))
                    ->orderByDesc('order')->orderBy('id')
                    ->first(),
                (clone $baseQuery)
                    ->where(fn ($q) => $q
                        ->where('order', '>', $article->order)
                        ->orWhere(fn ($q2) => $q2
                            ->where('order', '=', $article->order)
                            ->where('id', '<', $article->id)))
                    ->orderBy('order')->orderByDesc('id')
                    ->first(),
            ],
            // published_at_desc (por defecto)
            default => [
                (clone $baseQuery)
                    ->where(fn ($q) => $q
                        ->where('published_at', '>', $article->published_at)
                        ->orWhere(fn ($q2) => $q2
                            ->where('published_at', '=', $article->published_at)
                            ->where('id', '>', $article->id)))
                    ->orderBy('published_at')->orderBy('id')
                    ->first(),
                (clone $baseQuery)
                    ->where(fn ($q) => $q
                        ->where('published_at', '<', $article->published_at)
                        ->orWhere(fn ($q2) => $q2
                            ->where('published_at', '=', $article->published_at)
                            ->where('id', '<', $article->id)))
                    ->orderByDesc('published_at')->orderByDesc('id')
                    ->first(),
            ],
        };

        return [
            'anterior' => $anterior ? [
                'id'             => $anterior->id,
                'title'          => $anterior->title,
                'slug'           => $anterior->slug,
                'featured_image' => $anterior->featured_image,
                'url'            => url("/{$category->slug}/{$anterior->slug}"),
            ] : null,
            'siguiente' => $siguiente ? [
                'id'             => $siguiente->id,
                'title'          => $siguiente->title,
                'slug'           => $siguiente->slug,
                'featured_image' => $siguiente->featured_image,
                'url'            => url("/{$category->slug}/{$siguiente->slug}"),
            ] : null,
        ];
    }

    /**
     * Reordenar artículos manualmente dentro de una categoría.
     * Solo actualiza artículos que pertenezcan a la categoría indicada (seguridad).
     * El offset se usa para mantener coherencia entre páginas de la paginación.
     */
    public function reordenar(array $idsOrdenados, int $categoryId, int $offset = 0): void
    {
        foreach ($idsOrdenados as $posicion => $id) {
            Article::where('id', $id)
                ->where('category_id', $categoryId)
                ->update(['order' => $offset + $posicion]);
        }
    }

    /**
     * Obtener artículos publicados destacados.
     */
    public function destacados(int $limit = 5)
    {
        return Article::publicados()
            ->destacados()
            ->with(['category', 'author:id,name'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener los últimos artículos publicados.
     */
    public function ultimosPublicados(int $limit = 10)
    {
        return Article::publicados()
            ->with(['category', 'author:id,name'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener artículo publicado por slug (vista pública).
     */
    public function obtenerPublicadoPorSlug(string $slug): Article
    {
        return Article::publicados()
            ->with(['category.articleBuilderTemplate', 'author:id,name', 'articleBuilderTemplate'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Obtener artículos relacionados (misma categoría, excluyendo el actual).
     */
    public function relacionados(Article $article, int $limit = 4)
    {
        if (!$article->category_id) {
            return collect();
        }

        return Article::publicados()
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->with(['category', 'author:id,name'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Sanitizar campos de texto para evitar entidades &nbsp; innecesarias y soft hyphens indeseados.
     */
    protected function sanitizarDatos(array &$datos): void
    {
        if (isset($datos['body']) && is_string($datos['body'])) {
            $datos['body'] = str_replace(['&nbsp;', "\xC2\xAD", '&shy;'], [' ', '', ''], $datos['body']);
        }
        if (isset($datos['excerpt']) && is_string($datos['excerpt'])) {
            $datos['excerpt'] = str_replace(['&nbsp;', "\xC2\xAD", '&shy;'], [' ', '', ''], $datos['excerpt']);
        }
        if (isset($datos['title']) && is_string($datos['title'])) {
            $datos['title'] = str_replace(['&nbsp;', "\xC2\xAD", '&shy;'], [' ', '', ''], $datos['title']);
        }
    }
}
