<?php

namespace App\Services;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Models\Category;
use App\Models\Form;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Resolución de Campos Dinámicos para el Constructor de Páginas.
 *
 * Analiza la estructura JSON de la página (`layout_json`), detecta elementos con
 * vinculación a fuentes dinámicas (artículos, categorías, ajustes del sitio) y
 * resuelve los datos de forma atómica y optimizada sin consultas N+1.
 */
class PageBuilderResolverService
{
    /**
     * Resuelve y enriquece el árbol layout_json con datos dinámicos actualizados.
     *
     * @param array|null $layout
     * @return array|null
     */
    public function resolve(
        ?array $layout,
        ?int $excludeArticleId = null,
        bool $isArticleContext = false,
        ?int $excludeDownloadId = null
    ): ?array
    {
        if (empty($layout) || !isset($layout['sections']) || !is_array($layout['sections'])) {
            return $layout;
        }

        // 1. Recopilar IDs de categorías, fuentes, descargas y formularios en una sola pasada para Eager Loading
        $categoryIdsForArticles = [];
        $categoryIdsForDetail = [];
        $formSlugs = [];
        $downloadsQueries = [];

        $this->collectDynamicSources($layout['sections'], $categoryIdsForArticles, $categoryIdsForDetail, $formSlugs, $downloadsQueries);

        // 2. Carga atómica en lote (Batch Fetching) de las entidades vinculadas
        $articlesMap = $this->batchFetchArticles($categoryIdsForArticles, $excludeArticleId);
        $categoriesMap = $this->batchFetchCategories($categoryIdsForDetail);
        $formsMap = $this->batchFetchForms($formSlugs);
        $downloadsMap = $this->batchFetchDownloads($downloadsQueries, $excludeDownloadId);
        $siteSettings = SiteSetting::cached();

        // 3. Inyectar los datos resueltos en el árbol JSON
        $layout['sections'] = $this->injectResolvedData(
            $layout['sections'],
            $articlesMap,
            $categoriesMap,
            $formsMap,
            $siteSettings,
            $downloadsMap
        );

        // 4. Si existen elementos de paginación numérica sin resolver, vincularlos con el grid de la página (solo fuera de artículos)
        if (!$isArticleContext) {
            $this->resolverPaginacionGeneralSiExiste($layout['sections']);
        }

        // 5. En rutas públicas (fuera del editor / panel), podar etiquetas administrativas innecesarias para aligerar el payload SSR/SPA
        if (!request()->is('panel*', '*builder*')) {
            return self::limpiarLayoutParaPublico($layout);
        }

        return $layout;
    }

    /**
     * Elimina recursivamente metadatos exclusivos del editor (admin_label, name interno de filas/secciones y cadenas vacías de código)
     * cuando el layout viaja al frontend público, reduciendo el tamaño de data-page sin alterar el renderizado.
     */
    public static function limpiarLayoutParaPublico(?array $layout): ?array
    {
        if (empty($layout) || !isset($layout['sections']) || !is_array($layout['sections'])) {
            return $layout;
        }

        foreach ($layout['sections'] as &$section) {
            unset($section['admin_label'], $section['name']);
            if (isset($section['settings']) && is_array($section['settings'])) {
                foreach (['custom_css', 'custom_classes', 'custom_attributes', 'element_id'] as $emptyKey) {
                    if (isset($section['settings'][$emptyKey]) && $section['settings'][$emptyKey] === '') {
                        unset($section['settings'][$emptyKey]);
                    }
                }

                // Si la sección hereda el fondo claro en modo oscuro, omitir propiedades exclusivas de modo oscuro en el payload público
                if (($section['settings']['bg_dark_mode'] ?? 'inherit') !== 'custom') {
                    unset(
                        $section['settings']['bg_dark_mode'],
                        $section['settings']['bg_type_dark'],
                        $section['settings']['bg_image_dark'],
                        $section['settings']['bg_image_mobile_dark'],
                        $section['settings']['bg_size_dark'],
                        $section['settings']['bg_position_dark'],
                        $section['settings']['bg_size_tablet_dark'],
                        $section['settings']['bg_position_tablet_dark'],
                        $section['settings']['bg_size_mobile_dark'],
                        $section['settings']['bg_position_mobile_dark'],
                        $section['settings']['bg_attachment_dark'],
                        $section['settings']['bg_repeat_dark'],
                        $section['settings']['bg_blend_mode_dark'],
                        $section['settings']['bg_filter_dark'],
                        $section['settings']['bg_hide_mobile_dark'],
                        $section['settings']['bg_video_dark'],
                        $section['settings']['bg_video_poster_dark'],
                        $section['settings']['bg_video_mobile_dark'],
                        $section['settings']['bg_video_position_dark'],
                        $section['settings']['bg_video_playback_rate_dark'],
                        $section['settings']['bg_color_dark'],
                        $section['settings']['bg_overlay_dark'],
                        $section['settings']['text_color_dark']
                    );
                }
            }

            if (empty($section['rows']) || !is_array($section['rows'])) {
                continue;
            }

            foreach ($section['rows'] as &$row) {
                unset($row['admin_label'], $row['name']);
                foreach ($row['columns'] ?? [] as &$col) {
                    unset($col['admin_label'], $col['name']);
                    if (!empty($col['elements']) && is_array($col['elements'])) {
                        self::limpiarElementosParaPublico($col['elements']);
                    }
                }
                unset($col);
            }
            unset($row);
        }
        unset($section);

        return $layout;
    }

    /**
     * Poda metadatos administrativos en una lista de elementos (incluyendo sublayouts anidados).
     */
    private static function limpiarElementosParaPublico(array &$elements): void
    {
        foreach ($elements as &$el) {
            unset($el['admin_label'], $el['name']);
            if (isset($el['content']) && is_array($el['content'])) {
                unset($el['content']['admin_label']);
                foreach (['custom_css', 'custom_classes', 'custom_attributes', 'element_id'] as $emptyKey) {
                    if (isset($el['content'][$emptyKey]) && $el['content'][$emptyKey] === '') {
                        unset($el['content'][$emptyKey]);
                    }
                }

                if (($el['type'] ?? '') === 'sublayout' && !empty($el['content']['rows']) && is_array($el['content']['rows'])) {
                    foreach ($el['content']['rows'] as &$subRow) {
                        unset($subRow['admin_label'], $subRow['name']);
                        foreach ($subRow['columns'] ?? [] as &$subCol) {
                            unset($subCol['admin_label'], $subCol['name']);
                            if (!empty($subCol['elements']) && is_array($subCol['elements'])) {
                                self::limpiarElementosParaPublico($subCol['elements']);
                            }
                        }
                        unset($subCol);
                    }
                    unset($subRow);
                }
            }
        }
        unset($el);
    }

    /**
     * Recorre el árbol de elementos recopilando las fuentes dinámicas.
     */
    private function collectDynamicSources(
        array $sections,
        array &$categoryIdsForArticles,
        array &$categoryIdsForDetail,
        array &$formSlugs,
        array &$downloadsQueries = []
    ): void {
        foreach ($sections as $section) {
            foreach ($section['rows'] ?? [] as $row) {
                foreach ($row['columns'] ?? [] as $column) {
                    foreach ($column['elements'] ?? [] as $element) {
                        $binding = $element['content']['dynamic_binding'] ?? null;
                        $type = $element['type'] ?? '';

                        if ($type === 'form' && !empty($element['content']['form_slug'])) {
                            $formSlugs[(string) $element['content']['form_slug']] = true;
                        } elseif (($type === 'grid_articles' || $type === 'grid') && ($binding['source'] ?? '') === 'downloads') {
                            $orderBy = $binding['order_by'] ?? $element['content']['order_by'] ?? 'order_asc';
                            $requestedLimit = max(1, min(50, (int) ($binding['limit'] ?? $element['content']['limit'] ?? 12)));
                            $prevMax = $downloadsQueries[$orderBy] ?? 0;
                            $downloadsQueries[$orderBy] = max($prevMax, $requestedLimit);
                        } elseif ($type === 'grid_articles' || $type === 'grid' || ($binding && ($binding['source'] ?? '') === 'articles')) {
                            $catId = !empty($binding['category_id']) ? (string) $binding['category_id'] : 'all';
                            $orderBy = $binding['order_by'] ?? $element['content']['order_by'] ?? 'published_at_desc';
                            $defaultLimit = ($type === 'map') ? 50 : 6;
                            $requestedLimit = max(1, min(50, (int) ($binding['limit'] ?? $element['content']['limit'] ?? $defaultLimit)));
                            $key = "{$catId}_{$orderBy}";

                            $prevMax = $categoryIdsForArticles[$key]['max_limit'] ?? 0;
                            $categoryIdsForArticles[$key] = [
                                'category_id' => $catId,
                                'order_by'    => $orderBy,
                                'max_limit'   => max($prevMax, $requestedLimit),
                            ];
                        } elseif ($binding && ($binding['source'] ?? '') === 'category' && !empty($binding['category_id'])) {
                            $categoryIdsForDetail[(string) $binding['category_id']] = true;
                        }

                        // Recursividad para elementos sublayout
                        if ($type === 'sublayout' && !empty($element['content']['rows']) && is_array($element['content']['rows'])) {
                            $this->collectDynamicSourcesFromSubrows(
                                $element['content']['rows'],
                                $categoryIdsForArticles,
                                $categoryIdsForDetail,
                                $formSlugs,
                                $downloadsQueries
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Carga de forma atómica los artículos por categoría y criterio de ordenación respetando N+1.
     */
    private function batchFetchArticles(array $categoryIdsMap, ?int $excludeArticleId = null): array
    {
        if (empty($categoryIdsMap)) {
            return [];
        }

        $results = [];

        foreach ($categoryIdsMap as $key => $cfg) {
            $catKey   = $cfg['category_id'];
            $orderBy  = $cfg['order_by'] ?? 'published_at_desc';
            $maxLimit = max(1, min(50, (int) ($cfg['max_limit'] ?? 24)));

            // Excluir columnas pesadas de detalle (body, layout_json) que no se usan en tarjetas ni marcadores
            $query = Article::publicados()
                ->select([
                    'id', 'title', 'slug', 'excerpt', 'featured_image', 'icon', 'order',
                    'category_id', 'author_id', 'status', 'published_at', 'published_until',
                    'reading_time', 'meta_robots', 'show_read_more', 'card_full_link',
                    'read_more_text', 'article_builder_template_id', 'extra_fields_data',
                ])
                ->with(['category:id,name,slug,extra_fields_schema,show_author,show_date,show_reading_time', 'author:id,name']);

            if ($excludeArticleId) {
                $query->where('id', '!=', $excludeArticleId);
            }

            if ($catKey !== 'all' && is_numeric($catKey)) {
                $query->where('category_id', (int) $catKey);
            }

            switch ($orderBy) {
                case 'order_asc':
                    $query->orderBy('order', 'asc')->orderByDesc('published_at')->orderByDesc('id');
                    break;
                case 'published_at_asc':
                    $query->orderBy('published_at', 'asc')->orderBy('id', 'asc');
                    break;
                case 'title_asc':
                    $query->orderBy('title', 'asc');
                    break;
                case 'title_desc':
                    $query->orderBy('title', 'desc');
                    break;
                case 'published_at_desc':
                default:
                    $query->orderByDesc('published_at')->orderByDesc('id');
                    break;
            }

            $articles = $query->take($maxLimit)->get();
            $results[$key] = ArticleResource::collection($articles)->resolve();
        }

        return $results;
    }

    /**
     * Carga las categorías requeridas en un solo query.
     */
    private function batchFetchCategories(array $categoryIdsMap): array
    {
        if (empty($categoryIdsMap)) {
            return [];
        }

        return Category::whereIn('id', array_keys($categoryIdsMap))
            ->get()
            ->keyBy('id')
            ->toArray();
    }

    /**
     * Carga y formatea de forma atómica los esquemas públicos de formularios respetando N+1.
     */
    private function batchFetchForms(array $formSlugs): array
    {
        if (empty($formSlugs)) {
            return [];
        }

        return Form::active()
            ->whereIn('slug', array_keys($formSlugs))
            ->get()
            ->keyBy('slug')
            ->map(fn (Form $form) => $form->toPublicSchema())
            ->toArray();
    }

    /**
     * Carga de forma atómica los recursos del módulo autónomo de Descargas si está instalado.
     */
    private function batchFetchDownloads(array $downloadsQueries, ?int $excludeDownloadId = null): array
    {
        if (
            empty($downloadsQueries) ||
            !class_exists(\App\Modules\Downloads\Models\DownloadItem::class) ||
            !class_exists(\App\Modules\Downloads\Http\Resources\DownloadItemPublicResource::class)
        ) {
            return [];
        }

        $results = [];
        $req = request();

        foreach ($downloadsQueries as $orderBy => $maxLimit) {
            $query = \App\Modules\Downloads\Models\DownloadItem::publicados();

            if ($excludeDownloadId !== null) {
                $query->where('id', '!=', $excludeDownloadId);
            }

            switch ($orderBy) {
                case 'published_at_desc':
                    $query->orderByDesc('published_at')->orderByDesc('id');
                    break;
                case 'published_at_asc':
                    $query->orderBy('published_at', 'asc')->orderBy('id', 'asc');
                    break;
                case 'title_asc':
                    $query->orderBy('title', 'asc');
                    break;
                case 'title_desc':
                    $query->orderBy('title', 'desc');
                    break;
                case 'order_asc':
                default:
                    $query->orderBy('order', 'asc')->orderByDesc('id');
                    break;
            }

            $items = $query->take(max(1, min(50, (int) $maxLimit)))->get();
            $results[$orderBy] = $items->map(function ($item) use ($req) {
                $data = (new \App\Modules\Downloads\Http\Resources\DownloadItemPublicResource($item, false))->resolve($req);
                $data['excerpt'] = $data['short_description'] ?? '';
                $data['_source'] = 'downloads';

                return $data;
            })->values()->all();
        }

        return $results;
    }

    /**
     * Inyecta la propiedad 'resolved_data' directamente por índice de memoria.
     */
    private function injectResolvedData(
        array $sections,
        array $articlesMap,
        array $categoriesMap,
        array $formsMap,
        SiteSetting $settings,
        array $downloadsMap = []
    ): array {
        foreach ($sections as $sIdx => $section) {
            if (!isset($section['rows']) || !is_array($section['rows'])) {
                continue;
            }

            foreach ($section['rows'] as $rIdx => $row) {
                if (!isset($row['columns']) || !is_array($row['columns'])) {
                    continue;
                }

                foreach ($row['columns'] as $cIdx => $column) {
                    if (!isset($column['elements']) || !is_array($column['elements'])) {
                        continue;
                    }

                    foreach ($column['elements'] as $eIdx => $element) {
                        $binding = $element['content']['dynamic_binding'] ?? null;
                        $type = $element['type'] ?? '';

                        if ($type === 'form' && !empty($element['content']['form_slug'])) {
                            if (!isset($element['resolved_data'])) {
                                $slug = (string) $element['content']['form_slug'];
                                if (isset($formsMap[$slug])) {
                                    $sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = $formsMap[$slug];
                                }
                            }
                        } elseif ($type === 'map' && $binding && ($binding['source'] ?? '') === 'articles') {
                            if (isset($element['resolved_data'])) {
                                continue;
                            }

                            $catId = !empty($binding['category_id']) ? (string) $binding['category_id'] : 'all';
                            $orderBy = $binding['order_by'] ?? $element['content']['order_by'] ?? 'published_at_desc';
                            $key = "{$catId}_{$orderBy}";
                            $limit = (int) ($binding['limit'] ?? $element['content']['limit'] ?? 50);

                            $articlesList = $articlesMap[$key] ?? $articlesMap["{$catId}_published_at_desc"] ?? [];
                            $articlesSlice = array_slice($articlesList, 0, $limit);
                            $locationField = $binding['location_field'] ?? null;

                            $markers = $this->mapearArticulosAMarcadores($articlesSlice, $locationField, $element['content'] ?? []);
                            $sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = [
                                'markers' => $markers,
                            ];
                        } elseif (($type === 'grid_articles' || $type === 'grid') && ($binding['source'] ?? '') === 'downloads') {
                            if (isset($element['resolved_data'])) {
                                continue;
                            }

                            $orderBy = $binding['order_by'] ?? $element['content']['order_by'] ?? 'order_asc';
                            $limit = (int) ($binding['limit'] ?? $element['content']['limit'] ?? 12);
                            $dlList = $downloadsMap[$orderBy] ?? $downloadsMap['order_asc'] ?? [];
                            $sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = array_slice($dlList, 0, $limit);
                        } elseif ($type === 'grid_articles' || $type === 'grid' || ($binding && ($binding['source'] ?? '') === 'articles')) {
                            // Si ya fue resuelto previamente (ej. por inyectarArticulosCategoriaActual), no sobreescribir
                            if (isset($element['resolved_data'])) {
                                continue;
                            }

                            $catId = !empty($binding['category_id']) ? (string) $binding['category_id'] : 'all';
                            $orderBy = $binding['order_by'] ?? $element['content']['order_by'] ?? 'published_at_desc';
                            $key = "{$catId}_{$orderBy}";
                            $limit = (int) ($binding['limit'] ?? $element['content']['limit'] ?? 6);

                            $articlesList = $articlesMap[$key] ?? $articlesMap["{$catId}_published_at_desc"] ?? [];

                            $sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = array_slice($articlesList, 0, $limit);
                        } elseif ($binding && ($binding['source'] ?? '') === 'category') {
                            $catId = (string) ($binding['category_id'] ?? '');
                            $sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = $catId && isset($categoriesMap[$catId]) ? $categoriesMap[$catId] : null;
                        } elseif ($binding && ($binding['source'] ?? '') === 'site_setting') {
                            $field = $binding['field'] ?? 'site_name';
                            $sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = $settings->{$field} ?? '';
                        }

                        // Recursividad para elementos sublayout
                        if ($type === 'sublayout' && !empty($element['content']['rows']) && is_array($element['content']['rows'])) {
                            $sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'][$eIdx]['content']['rows'] = $this->injectResolvedDataIntoSubrows(
                                $element['content']['rows'],
                                $articlesMap,
                                $categoriesMap,
                                $formsMap,
                                $settings,
                                $downloadsMap
                            );
                        }
                    }
                }
            }
        }

        return $sections;
    }

    /**
     * Recorre recursivamente las filas de un sublayout recopilando fuentes dinámicas.
     */
    private function collectDynamicSourcesFromSubrows(
        array $rows,
        array &$categoryIdsForArticles,
        array &$categoryIdsForDetail,
        array &$formSlugs,
        array &$downloadsQueries = []
    ): void {
        foreach ($rows as $row) {
            foreach ($row['columns'] ?? [] as $column) {
                foreach ($column['elements'] ?? [] as $element) {
                    $binding = $element['content']['dynamic_binding'] ?? null;
                    $type = $element['type'] ?? '';

                    if ($type === 'form' && !empty($element['content']['form_slug'])) {
                        $formSlugs[(string) $element['content']['form_slug']] = true;
                    } elseif (($type === 'grid_articles' || $type === 'grid') && ($binding['source'] ?? '') === 'downloads') {
                        $orderBy = $binding['order_by'] ?? $element['content']['order_by'] ?? 'order_asc';
                        $requestedLimit = max(1, min(50, (int) ($binding['limit'] ?? $element['content']['limit'] ?? 12)));
                        $prevMax = $downloadsQueries[$orderBy] ?? 0;
                        $downloadsQueries[$orderBy] = max($prevMax, $requestedLimit);
                    } elseif ($type === 'grid_articles' || $type === 'grid' || ($binding && ($binding['source'] ?? '') === 'articles')) {
                        $catId = !empty($binding['category_id']) ? (string) $binding['category_id'] : 'all';
                        $orderBy = $binding['order_by'] ?? $element['content']['order_by'] ?? 'published_at_desc';
                        $defaultLimit = ($type === 'map') ? 50 : 6;
                        $requestedLimit = max(1, min(50, (int) ($binding['limit'] ?? $element['content']['limit'] ?? $defaultLimit)));
                        $key = "{$catId}_{$orderBy}";

                        $prevMax = $categoryIdsForArticles[$key]['max_limit'] ?? 0;
                        $categoryIdsForArticles[$key] = [
                            'category_id' => $catId,
                            'order_by'    => $orderBy,
                            'max_limit'   => max($prevMax, $requestedLimit),
                        ];
                    } elseif ($binding && ($binding['source'] ?? '') === 'category' && !empty($binding['category_id'])) {
                        $categoryIdsForDetail[(string) $binding['category_id']] = true;
                    }

                    if ($type === 'sublayout' && !empty($element['content']['rows']) && is_array($element['content']['rows'])) {
                        $this->collectDynamicSourcesFromSubrows(
                            $element['content']['rows'],
                            $categoryIdsForArticles,
                            $categoryIdsForDetail,
                            $formSlugs,
                            $downloadsQueries
                        );
                    }
                }
            }
        }
    }

    /**
     * Inyecta recursivamente datos resueltos en las filas de un sublayout.
     */
    private function injectResolvedDataIntoSubrows(
        array $rows,
        array $articlesMap,
        array $categoriesMap,
        array $formsMap,
        SiteSetting $settings,
        array $downloadsMap = []
    ): array {
        foreach ($rows as $rIdx => $row) {
            foreach ($row['columns'] ?? [] as $cIdx => $column) {
                foreach ($column['elements'] ?? [] as $eIdx => $element) {
                    $binding = $element['content']['dynamic_binding'] ?? null;
                    $type = $element['type'] ?? '';

                    if ($type === 'form' && !empty($element['content']['form_slug'])) {
                        if (!isset($element['resolved_data'])) {
                            $slug = (string) $element['content']['form_slug'];
                            if (isset($formsMap[$slug])) {
                                $rows[$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = $formsMap[$slug];
                            }
                        }
                    } elseif ($type === 'map' && $binding && ($binding['source'] ?? '') === 'articles') {
                        if (isset($element['resolved_data'])) {
                            continue;
                        }

                        $catId = !empty($binding['category_id']) ? (string) $binding['category_id'] : 'all';
                        $orderBy = $binding['order_by'] ?? $element['content']['order_by'] ?? 'published_at_desc';
                        $key = "{$catId}_{$orderBy}";
                        $limit = (int) ($binding['limit'] ?? $element['content']['limit'] ?? 50);

                        $articlesList = $articlesMap[$key] ?? $articlesMap["{$catId}_published_at_desc"] ?? [];
                        $articlesSlice = array_slice($articlesList, 0, $limit);
                        $locationField = $binding['location_field'] ?? null;

                        $markers = $this->mapearArticulosAMarcadores($articlesSlice, $locationField, $element['content'] ?? []);
                        $rows[$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = [
                            'markers' => $markers,
                        ];
                    } elseif (($type === 'grid_articles' || $type === 'grid') && ($binding['source'] ?? '') === 'downloads') {
                        if (isset($element['resolved_data'])) {
                            continue;
                        }

                        $orderBy = $binding['order_by'] ?? $element['content']['order_by'] ?? 'order_asc';
                        $limit = (int) ($binding['limit'] ?? $element['content']['limit'] ?? 12);
                        $dlList = $downloadsMap[$orderBy] ?? $downloadsMap['order_asc'] ?? [];
                        $rows[$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = array_slice($dlList, 0, $limit);
                    } elseif ($type === 'grid_articles' || $type === 'grid' || ($binding && ($binding['source'] ?? '') === 'articles')) {
                        if (isset($element['resolved_data'])) {
                            continue;
                        }

                        $catId = !empty($binding['category_id']) ? (string) $binding['category_id'] : 'all';
                        $orderBy = $binding['order_by'] ?? $element['content']['order_by'] ?? 'published_at_desc';
                        $key = "{$catId}_{$orderBy}";
                        $limit = (int) ($binding['limit'] ?? $element['content']['limit'] ?? 6);

                        $articlesList = $articlesMap[$key] ?? $articlesMap["{$catId}_published_at_desc"] ?? [];
                        $rows[$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = array_slice($articlesList, 0, $limit);
                    } elseif ($binding && ($binding['source'] ?? '') === 'category') {
                        $catId = (string) ($binding['category_id'] ?? '');
                        $rows[$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = $catId && isset($categoriesMap[$catId]) ? $categoriesMap[$catId] : null;
                    } elseif ($binding && ($binding['source'] ?? '') === 'site_setting') {
                        $field = $binding['field'] ?? 'site_name';
                        $rows[$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = $settings->{$field} ?? '';
                    }

                    if ($type === 'sublayout' && !empty($element['content']['rows']) && is_array($element['content']['rows'])) {
                        $rows[$rIdx]['columns'][$cIdx]['elements'][$eIdx]['content']['rows'] = $this->injectResolvedDataIntoSubrows(
                            $element['content']['rows'],
                            $articlesMap,
                            $categoriesMap,
                            $formsMap,
                            $settings,
                            $downloadsMap
                        );
                    }
                }
            }
        }

        return $rows;
    }

    protected ArticleService $articleService;

    public function __construct(?ArticleService $articleService = null)
    {
        $this->articleService = $articleService ?? app(ArticleService::class);
    }

    /**
     * Resuelve y enriquece una plantilla de detalle de artículo con los datos del artículo activo.
     */
    public function resolveForArticle(?array $layout, Article $article): ?array
    {
        if (empty($layout) || !isset($layout['sections']) || !is_array($layout['sections'])) {
            return $layout;
        }

        // Cargar relaciones requeridas si no están cargadas
        if (!$article->relationLoaded('category')) {
            $article->load('category:id,name,slug,extra_fields_schema,articles_order');
        }
        if (!$article->relationLoaded('author')) {
            $article->load('author:id,name');
        }

        $diccionario = $this->generarDiccionarioArticulo($article);

        // 1. Sustituir etiquetas y orígenes dinámicos en todo el árbol
        $layout['sections'] = $this->sustituirDiccionarioEnSecciones($layout['sections'], $diccionario, [
            'article' => $article,
        ]);

         // 2. Inyectar navegación secuencial, paginación numérica de serie y galerías dinámicas del artículo actual
        if ($article->category) {
            $navegacion = $this->articleService->obtenerNavegacionAnteriorYSiguiente($article, $article->category);
            $paginacionArticulos = $this->generarPaginacionArticulosCategoria($article, $article->category);
            $articleResource = (new \App\Http\Resources\ArticleResource($article))->resolve();

            foreach ($layout['sections'] as $sIdx => $sec) {
                if (empty($sec['rows']) || !is_array($sec['rows'])) continue;
                foreach ($sec['rows'] as $rIdx => $row) {
                    if (empty($row['columns']) || !is_array($row['columns'])) continue;
                    foreach ($row['columns'] as $cIdx => $col) {
                        if (!empty($col['elements']) && is_array($col['elements'])) {
                            $this->inyectarDatosArticuloEnElementos(
                                $layout['sections'][$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'],
                                $navegacion,
                                $paginacionArticulos,
                                $articleResource
                            );
                        }
                    }
                }
            }
        }

        // 3. Resolver posibles grids secundarios (ej. artículos relacionados o de otras categorías)
        return $this->resolve($layout, $article->id, true);
    }

    /**
     * Resuelve y enriquece una plantilla de listado de categoría con los datos de la categoría activa.
     */
    public function resolveForCategory(?array $layout, Category $category, mixed $articulosPaginados = null): ?array
    {
        if (empty($layout) || !isset($layout['sections']) || !is_array($layout['sections'])) {
            return $layout;
        }

        $diccionario = $this->generarDiccionarioCategoria($category);

        // 1. Sustituir etiquetas y orígenes dinámicos
        $layout['sections'] = $this->sustituirDiccionarioEnSecciones($layout['sections'], $diccionario, [
            'category' => $category,
            'paginated_articles' => $articulosPaginados,
        ]);

        // 2. Resolver o preparar artículos paginados respetando el orden de la categoría
        if ($articulosPaginados === null) {
            $perPage = (int) ($category->per_page ?? 12);
            $pageNumber = (int) request('page', 1);
            $query = Article::publicados()
                ->where('category_id', $category->id)
                ->with(['category:id,name,slug,extra_fields_schema', 'author:id,name']);

            $orderType = $category->articles_order ?? 'published_at_desc';
            match ($orderType) {
                'manual'           => $query->orderBy('order', 'asc')->orderByDesc('id'),
                'published_at_asc' => $query->orderBy('published_at', 'asc')->orderBy('id', 'asc'),
                'title_asc'        => $query->orderBy('title', 'asc'),
                'title_desc'       => $query->orderBy('title', 'desc'),
                default            => $query->orderByDesc('published_at')->orderByDesc('id'),
            };

            $articulosPaginados = $query->paginate($perPage, ['*'], 'page', $pageNumber);
        }

        if ($articulosPaginados && method_exists($articulosPaginados, 'withPath')) {
            $articulosPaginados->withPath(url('/' . ltrim($category->slug, '/')));
        }

        // 3. Resolver grids dinámicos configurados con 'current_category'
        $layout = $this->inyectarArticulosCategoriaActual($layout, $category, $articulosPaginados);

        // 4. Inyectar metadatos de paginación numérica si existen elementos de paginación
        if ($articulosPaginados && method_exists($articulosPaginados, 'linkCollection')) {
            $paginacionData = [
                'links'        => self::formatearLinksPaginacion($articulosPaginados, $category->slug),
                'current_page' => $articulosPaginados->currentPage(),
                'last_page'    => $articulosPaginados->lastPage(),
                'total'        => $articulosPaginados->total(),
                'base_slug'    => $category->slug,
            ];

            foreach ($layout['sections'] as $sIdx => $sec) {
                if (empty($sec['rows']) || !is_array($sec['rows'])) continue;
                foreach ($sec['rows'] as $rIdx => $row) {
                    if (empty($row['columns']) || !is_array($row['columns'])) continue;
                    foreach ($row['columns'] as $cIdx => $col) {
                        if (!empty($col['elements']) && is_array($col['elements'])) {
                            $this->inyectarPaginacionEnElementos(
                                $layout['sections'][$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'],
                                $paginacionData
                            );
                        }
                    }
                }
            }
        }

        return $this->resolve($layout);
    }

    /**
     * Genera el diccionario clave-valor para sustitución de etiquetas de artículo.
     */
    private function generarDiccionarioArticulo(Article $article): array
    {
        $fechaPublicacion = $article->published_at ? $article->published_at->format('d/m/Y') : '';
        $nombreCategoria  = $article->category?->name ?? '';
        $urlCategoria     = $article->category ? url("/{$article->category->slug}") : '#';
        $slugCategoria    = $article->category?->slug ?? '';
        $descCategoria    = $article->category?->description ?? '';
        $imgCategoria     = $article->category?->featured_image ?? '';
        $iconCategoria    = $article->category?->icon ?? '';
        $colorCategoria   = $article->category?->color ?? '';
        $nombreAutor      = $article->author?->name ?? 'Redacción';
        $tiempoLectura    = (string) ($article->reading_time ?? 1);

        $settings = \App\Models\SiteSetting::cached();
        $siteName = $settings->site_name ?? '';
        $siteDesc = $settings->site_description ?? '';

        $diccionario = [
            '{article.title}'           => $article->title ?? '',
            '{article.name}'            => $article->title ?? '',
            '{article.subtitle}'        => $article->excerpt ?? '',
            '{article.excerpt}'         => $article->excerpt ?? '',
            '{article.body}'            => $article->body ?? '',
            '{article.content}'         => $article->body ?? '',
            '{article.featured_image}'  => $article->featured_image ?? '',
            '{article.image}'           => $article->featured_image ?? '',
            '{article.icon}'            => $article->icon ?? '',
            '{article.published_at}'    => $fechaPublicacion,
            '{article.date}'            => $fechaPublicacion,
            '{article.author.name}'     => $nombreAutor,
            '{article.author}'          => $nombreAutor,
            '{article.category.name}'   => $nombreCategoria,
            '{article.category}'        => $nombreCategoria,
            '{article.category.url}'    => $urlCategoria,
            '{article.category.slug}'   => $slugCategoria,
            '{article.reading_time}'    => $tiempoLectura,
            '{article.url}'             => url("/" . ($article->category?->slug ?? 'articulos') . "/{$article->slug}"),

            // Variables de categoría heredadas en la vista de artículo
            '{category.name}'           => $nombreCategoria,
            '{category.title}'          => $nombreCategoria,
            '{category.description}'   => $descCategoria,
            '{category.featured_image}' => $imgCategoria,
            '{category.image}'          => $imgCategoria,
            '{category.slug}'           => $slugCategoria,
            '{category.icon}'           => $iconCategoria,
            '{category.color}'          => $colorCategoria,
            '{category.url}'            => $urlCategoria,

            // Variables globales de sitio
            '{site.name}'               => $siteName,
            '{site.title}'              => $siteName,
            '{site.description}'        => $siteDesc,
        ];

        // 1. Inicializar campos extra del esquema de la categoría en vacío para sustitución limpia
        if ($article->category && is_array($article->category->extra_fields_schema)) {
            foreach ($article->category->extra_fields_schema as $field) {
                $fKey = (string) ($field['key'] ?? '');
                if ($fKey !== '') {
                    $kLower = strtolower($fKey);
                    $diccionario["{article.extra_fields.{$fKey}}"] = '';
                    $diccionario["{article.extra_fields.{$kLower}}"] = '';
                    $diccionario["{extra.{$fKey}}"] = '';
                    $diccionario["{extra.{$kLower}}"] = '';
                }
            }
        }

        // 2. Mapear valores reales de los campos extra dinámicos del artículo
        if (!empty($article->extra_fields_data) && is_array($article->extra_fields_data)) {
            $atributosReservados = [
                'title', 'name', 'subtitle', 'excerpt', 'body', 'content',
                'featured_image', 'image', 'icon', 'published_at', 'date',
                'author', 'category', 'reading_time', 'url'
            ];

            foreach ($article->extra_fields_data as $key => $val) {
                $keyStr = (string) $key;
                $kLower = strtolower($keyStr);
                $valStr = (is_scalar($val) && $val !== null) ? (string) $val : '';

                $diccionario["{article.extra_fields.{$keyStr}}"] = $valStr;
                $diccionario["{article.extra_fields.{$kLower}}"] = $valStr;
                $diccionario["{extra.{$keyStr}}"] = $valStr;
                $diccionario["{extra.{$kLower}}"] = $valStr;

                // Alias directo si no colisiona con atributos nativos
                if (!in_array($kLower, $atributosReservados, true)) {
                    $diccionario["{article.{$keyStr}}"] = $valStr;
                    $diccionario["{article.{$kLower}}"] = $valStr;
                }
            }
        }

        return $diccionario;
    }

    /**
     * Genera el diccionario clave-valor para sustitución de etiquetas de categoría.
     */
    private function generarDiccionarioCategoria(Category $category): array
    {
        $settings = \App\Models\SiteSetting::cached();
        $siteName = $settings->site_name ?? '';
        $siteDesc = $settings->site_description ?? '';

        return [
            '{category.name}'           => $category->name ?? '',
            '{category.title}'          => $category->name ?? '',
            '{category.description}'   => $category->description ?? '',
            '{category.featured_image}' => $category->featured_image ?? '',
            '{category.image}'          => $category->featured_image ?? '',
            '{category.slug}'           => $category->slug ?? '',
            '{category.icon}'           => $category->icon ?? '',
            '{category.color}'          => $category->color ?? '',
            '{category.url}'            => url("/{$category->slug}"),

            // Variables globales de sitio
            '{site.name}'               => $siteName,
            '{site.title}'              => $siteName,
            '{site.description}'        => $siteDesc,
        ];
    }

    /**
     * Sustituye recursivamente las etiquetas del diccionario en el árbol de secciones.
     */
    private function sustituirDiccionarioEnSecciones(array $sections, array $diccionario, array $contexto = []): array
    {
        // Normalizar claves a minúsculas para búsqueda totalmente insensible a mayúsculas/minúsculas
        $mapaNormalizado = [];
        foreach ($diccionario as $k => $v) {
            $mapaNormalizado[strtolower(trim((string)$k))] = (string)$v;
        }

        // Función de reemplazo robusta e insensible a mayúsculas (ej: {CATEGORY.NAME}, {Category.Name}, {article.body})
        $reemplazarTexto = function (string $texto) use ($mapaNormalizado): string {
            if ($texto === '') return $texto;
            return preg_replace_callback('/\{([a-zA-Z0-9_.\-]+)\}/i', function ($matches) use ($mapaNormalizado) {
                $clave = strtolower($matches[0]);
                return array_key_exists($clave, $mapaNormalizado) ? $mapaNormalizado[$clave] : $matches[0];
            }, $texto);
        };

        foreach ($sections as $sIdx => $section) {
            // Sustituir variables en los ajustes de la sección (ej: bg_image, custom_css, etc.)
            if (!empty($section['settings']) && is_array($section['settings'])) {
                foreach ($section['settings'] as $sk => $sv) {
                    if (is_string($sv) && !empty($sv)) {
                        $sections[$sIdx]['settings'][$sk] = $reemplazarTexto($sv);
                    }
                }
            }

            foreach ($section['rows'] ?? [] as $rIdx => $row) {
                // Sustituir en los ajustes de la fila si existen
                if (!empty($row['settings']) && is_array($row['settings'])) {
                    foreach ($row['settings'] as $rk => $rv) {
                        if (is_string($rv) && !empty($rv)) {
                            $sections[$sIdx]['rows'][$rIdx]['settings'][$rk] = $reemplazarTexto($rv);
                        }
                    }
                }

                foreach ($row['columns'] ?? [] as $cIdx => $column) {
                    // Sustituir en los ajustes de la columna si existen
                    if (!empty($column['settings']) && is_array($column['settings'])) {
                        foreach ($column['settings'] as $ck => $cv) {
                            if (is_string($cv) && !empty($cv)) {
                                $sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['settings'][$ck] = $reemplazarTexto($cv);
                            }
                        }
                    }
                    if (isset($column['bg_image']) && is_string($column['bg_image']) && !empty($column['bg_image'])) {
                        $sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['bg_image'] = $reemplazarTexto($column['bg_image']);
                    }

                    foreach ($column['elements'] ?? [] as $eIdx => $element) {
                        $content = $element['content'] ?? [];
                        $dynamicSource = $content['dynamic_source'] ?? null;

                        // Sustitución por dynamic_source explícito
                        if ($dynamicSource) {
                            $sourceTag = '{' . strtolower(trim((string)$dynamicSource)) . '}';
                            if (isset($mapaNormalizado[$sourceTag])) {
                                $valor = $mapaNormalizado[$sourceTag];
                                if (isset($content['text'])) $content['text'] = $valor;
                                if (isset($content['html'])) $content['html'] = $valor;
                                if (isset($content['url']) && ($element['type'] ?? '') === 'image') $content['url'] = $valor;
                            }
                        }

                        // Sustitución de etiquetas en cadenas de texto
                        foreach ($content as $k => $v) {
                            if (is_string($v) && !empty($v)) {
                                $content[$k] = $reemplazarTexto($v);
                            }
                        }

                        // Si el elemento contiene un array de items (ej: marcadores de mapa, acordeón, etc.), sustituir dentro de cada item
                        if (isset($content['items']) && is_array($content['items'])) {
                            foreach ($content['items'] as $itemIdx => $item) {
                                if (is_array($item)) {
                                    foreach ($item as $ik => $iv) {
                                        if (is_string($iv) && !empty($iv)) {
                                            $content['items'][$itemIdx][$ik] = $reemplazarTexto($iv);
                                        }
                                    }
                                }
                            }
                        }

                        // Si el elemento es un sublayout, sustituir también recursivamente en sus filas internas
                        if (($element['type'] ?? '') === 'sublayout' && !empty($content['rows']) && is_array($content['rows'])) {
                            $content['rows'] = $this->sustituirDiccionarioEnSubrows($content['rows'], $reemplazarTexto, $mapaNormalizado);
                        }

                        $sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'][$eIdx]['content'] = $content;
                    }
                }
            }
        }

        return $sections;
    }

    /**
     * Sustituye recursivamente las etiquetas del diccionario en las filas de un sublayout.
     */
    private function sustituirDiccionarioEnSubrows(array $rows, callable $reemplazarTexto, array $mapaNormalizado): array
    {
        foreach ($rows as $rIdx => $row) {
            foreach ($row['columns'] ?? [] as $cIdx => $column) {
                foreach ($column['elements'] ?? [] as $eIdx => $element) {
                    $content = $element['content'] ?? [];
                    $dynamicSource = $content['dynamic_source'] ?? null;

                    if ($dynamicSource) {
                        $sourceTag = '{' . strtolower(trim((string)$dynamicSource)) . '}';
                        if (isset($mapaNormalizado[$sourceTag])) {
                            $valor = $mapaNormalizado[$sourceTag];
                            if (isset($content['text'])) $content['text'] = $valor;
                            if (isset($content['html'])) $content['html'] = $valor;
                            if (isset($content['url']) && ($element['type'] ?? '') === 'image') $content['url'] = $valor;
                        }
                    }

                    foreach ($content as $k => $v) {
                        if (is_string($v) && !empty($v)) {
                            $content[$k] = $reemplazarTexto($v);
                        }
                    }

                    // Si el elemento contiene un array de items (ej: marcadores de mapa, acordeón, etc.), sustituir dentro de cada item
                    if (isset($content['items']) && is_array($content['items'])) {
                        foreach ($content['items'] as $itemIdx => $item) {
                            if (is_array($item)) {
                                foreach ($item as $ik => $iv) {
                                    if (is_string($iv) && !empty($iv)) {
                                        $content['items'][$itemIdx][$ik] = $reemplazarTexto($iv);
                                    }
                                }
                            }
                        }
                    }

                    if (($element['type'] ?? '') === 'sublayout' && !empty($content['rows']) && is_array($content['rows'])) {
                        $content['rows'] = $this->sustituirDiccionarioEnSubrows($content['rows'], $reemplazarTexto, $mapaNormalizado);
                    }

                    $rows[$rIdx]['columns'][$cIdx]['elements'][$eIdx]['content'] = $content;
                }
            }
        }

        return $rows;
    }

    /**
     * Inyecta los artículos de la categoría actual en elementos grid que usen 'current_category'.
     */
    private function injectCurrentCategoryArticles(array $layout, Category $category, mixed $articulosPaginados): array
    {
        if (empty($layout['sections']) || !is_array($layout['sections'])) {
            return $layout;
        }

        $articulosArray = null;
        if ($articulosPaginados) {
            $articulosArray = ArticleResource::collection($articulosPaginados)->resolve();
        }

        foreach ($layout['sections'] as $sIdx => $section) {
            foreach ($section['rows'] ?? [] as $rIdx => $row) {
                foreach ($row['columns'] ?? [] as $cIdx => $column) {
                    foreach ($column['elements'] ?? [] as $eIdx => $element) {
                        $binding = $element['content']['dynamic_binding'] ?? null;
                        $source  = $binding['source'] ?? '';

                        // Inyectar artículos de la categoría activa si:
                        // 1. source === 'current_category' (explícito desde el inspector)
                        // 2. category_id === 'current' (valor del selector de origen)
                        // 3. Grid sin categoría especificada (compatibilidad con plantillas existentes)
                        $esCategoriaActiva = $source === 'current_category'
                            || ($binding['category_id'] ?? '') === 'current'
                            || (($element['type'] ?? '') === 'grid_articles' && empty($binding['category_id']));

                        if ($esCategoriaActiva) {
                            if ($articulosArray === null) {
                                $query = Article::publicados()
                                    ->where('category_id', $category->id)
                                    ->with(['category:id,name,slug,extra_fields_schema', 'author:id,name']);

                                // Respetar el criterio de orden configurado en la categoría
                                $orderType = $category->articles_order ?? 'published_at_desc';
                                match ($orderType) {
                                    'manual' => $query->orderBy('order', 'asc')->orderByDesc('id'),
                                    'published_at_asc' => $query->orderBy('published_at', 'asc')->orderBy('id', 'asc'),
                                    'title_asc' => $query->orderBy('title', 'asc'),
                                    'title_desc' => $query->orderBy('title', 'desc'),
                                    default => $query->orderByDesc('published_at')->orderByDesc('id'),
                                };

                                $articulosArray = ArticleResource::collection(
                                    $query->take((int) ($binding['limit'] ?? 12))->get()
                                )->resolve();
                            }
                            $layout['sections'][$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = $articulosArray;
                        }
                    }
                }
            }
        }

        return $layout;
    }

    /**
     * Inyecta artículos de la categoría actual.
     */
    private function inyectarArticulosCategoriaActual(array $layout, Category $category, mixed $articulosPaginados): array
    {
        return $this->injectCurrentCategoryArticles($layout, $category, $articulosPaginados);
    }

    /**
     * Extrae ítems de galería a partir de los campos extra de imagen de un artículo resuelto.
     *
     * Recorre los campos seleccionados, extrae su valor del extra_fields_data del recurso,
     * y devuelve un array de objetos con formato { src, alt, title, type } compatibles
     * con el componente ElementGallery del frontend.
     *
     * @param array  $articleResource  Artículo resuelto via ArticleResource::resolve()
     * @param array  $selectedFields   Claves de los campos de imagen seleccionados
     * @return array  Lista de ítems de galería (solo campos con valor)
     */
    private function extraerItemsGaleriaDeArticulo(array $articleResource, array $selectedFields): array
    {
        $items = [];
        $extraData = $articleResource['extra_fields_data'] ?? [];

        foreach ($selectedFields as $fieldKey) {
            $src = $extraData[$fieldKey] ?? null;
            if (!$src || !is_string($src) || !trim($src)) {
                continue; // Omitir campos vacíos
            }

            $items[] = [
                'src'     => $src,
                'alt'     => $articleResource['title'] ?? '',
                'title'   => '',
                'caption' => '',
                'tags'    => '',
                'type'    => 'image',
            ];
        }

        return $items;
    }

    /**
     * Inyecta recursivamente marcadores de mapa en sublayouts para plantillas de artículo individual.
     */
    private function inyectarMapaArticuloEnSubrows(array $rows, array $articleResource): array
    {
        foreach ($rows as $rIdx => $row) {
            foreach ($row['columns'] ?? [] as $cIdx => $col) {
                foreach ($col['elements'] ?? [] as $eIdx => $elem) {
                    $elemType = $elem['type'] ?? '';
                    if ($elemType === 'map'
                        && ($elem['content']['mode'] ?? '') === 'dynamic'
                        && ($elem['content']['dynamic_binding']['source'] ?? '') === 'current_article'
                    ) {
                        $locationField = $elem['content']['dynamic_binding']['location_field'] ?? null;
                        $marker = $this->mapearArticuloAMarcador($articleResource, $locationField, $elem['content'] ?? []);
                        $rows[$rIdx]['columns'][$cIdx]['elements'][$eIdx]['resolved_data'] = [
                            'markers' => $marker ? [$marker] : [],
                        ];
                    }

                    if ($elemType === 'sublayout' && !empty($elem['content']['rows']) && is_array($elem['content']['rows'])) {
                        $rows[$rIdx]['columns'][$cIdx]['elements'][$eIdx]['content']['rows'] = $this->inyectarMapaArticuloEnSubrows(
                            $elem['content']['rows'],
                            $articleResource
                        );
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Transforma una lista de artículos resueltos en una colección de marcadores para el componente mapa.
     *
     * @param array $articles
     * @param string|null $locationField
     * @param array $content
     * @return array
     */
    public function mapearArticulosAMarcadores(array $articles, ?string $locationField = null, array $content = []): array
    {
        $markers = [];

        foreach ($articles as $article) {
            $marker = $this->mapearArticuloAMarcador($article, $locationField, $content);
            if ($marker !== null) {
                $markers[] = $marker;
            }
        }

        return $markers;
    }

    /**
     * Extrae y normaliza un marcador de mapa a partir de un recurso de artículo.
     * Soporta coordenadas ("lat, lng", {"lat": ..., "lng": ...}) o dirección de texto.
     *
     * @param array $article
     * @param string|null $locationField
     * @param array $content
     * @return array|null
     */
    public function mapearArticuloAMarcador(array $article, ?string $locationField = null, array $content = []): ?array
    {
        $extraData = $article['extra_fields_data'] ?? [];
        $rawLocation = null;

        // 1. Si se especificó un campo concreto, buscarlo en extra_fields_data o raíz del artículo
        if (!empty($locationField)) {
            $rawLocation = $extraData[$locationField] ?? $article[$locationField] ?? null;
        }

        // 2. Si no se especificó o no tiene valor, buscar heurísticamente en claves comunes de ubicación
        if (empty($rawLocation)) {
            $commonKeys = ['coordenadas', 'ubicacion', 'location', 'lat_lng', 'mapa', 'direccion', 'address', 'coordinates'];
            foreach ($commonKeys as $key) {
                if (!empty($extraData[$key])) {
                    $rawLocation = $extraData[$key];
                    break;
                }
            }
        }

        // Si no se encontró ningún dato de ubicación, omitir
        if (empty($rawLocation)) {
            return null;
        }

        $lat = null;
        $lng = null;
        $address = '';

        if (is_array($rawLocation)) {
            $lat = isset($rawLocation['lat']) ? (float) $rawLocation['lat'] : (isset($rawLocation['latitude']) ? (float) $rawLocation['latitude'] : null);
            $lng = isset($rawLocation['lng']) ? (float) $rawLocation['lng'] : (isset($rawLocation['longitude']) ? (float) $rawLocation['longitude'] : null);
            $address = $rawLocation['address'] ?? $rawLocation['direccion'] ?? '';
        } elseif (is_string($rawLocation)) {
            $rawLocation = trim($rawLocation);
            // Comprobar formato latitud, longitud (ej: "40.416775, -3.703790")
            if (preg_match('/^\s*(-?\d+(\.\d+)?)\s*,\s*(-?\d+(\.\d+)?)\s*$/', $rawLocation, $m)) {
                $lat = (float) $m[1];
                $lng = (float) $m[3];
            } else {
                $address = $rawLocation;
            }
        }

        // Construir URL del artículo
        $categorySlug = $article['category']['slug'] ?? 'general';
        $articleSlug = $article['slug'] ?? '';
        $articleUrl = $articleSlug ? url("/{$categorySlug}/{$articleSlug}") : '';

        // Título y contenido dinámico configurables
        $titleField = $content['title_field'] ?? 'title';
        $title = $titleField === 'title' ? ($article['title'] ?? '') : ($extraData[$titleField] ?? $article['title'] ?? '');

        $descField = $content['description_field'] ?? 'excerpt';
        $description = $descField === 'excerpt' ? ($article['excerpt'] ?? '') : ($extraData[$descField] ?? $article['excerpt'] ?? '');

        return [
            'id'          => 'art_map_' . ($article['id'] ?? uniqid()),
            'title'       => $title,
            'description' => $description,
            'lat'         => $lat,
            'lng'         => $lng,
            'address'     => $address,
            'icon_url'    => $article['featured_image'] ?? $article['icon'] ?? '',
            'link_url'    => $articleUrl,
            'link_text'   => $content['link_text'] ?? 'Ver detalles',
            'open_popup'  => false,
        ];
    }

    /**
     * Inyecta datos dinámicos del artículo (navegación, paginación numérica de serie, galerías, mapas)
     * en el árbol de elementos de forma recursiva (incluyendo sublayouts).
     */
    private function inyectarDatosArticuloEnElementos(
        array &$elements,
        array $navegacion,
        array $paginacionArticulos,
        array $articleResource
    ): void {
        foreach ($elements as &$elem) {
            $elemType = $elem['type'] ?? '';

            // Paginación (proporciona datos completos tanto para navegación de artículos como para paginación numérica)
            if ($elemType === 'pagination') {
                $elem['resolved_data'] = [
                    'navegacion' => $navegacion,
                    'paginacion' => $paginacionArticulos,
                ];
            }

            // Galería dinámica con origen "artículo actual"
            if ($elemType === 'gallery'
                && ($elem['content']['mode'] ?? '') === 'dynamic'
                && ($elem['content']['dynamic_binding']['source'] ?? '') === 'current_article'
            ) {
                $selectedFields = $elem['content']['dynamic_binding']['selected_image_fields'] ?? [];
                $galleryItems = $this->extraerItemsGaleriaDeArticulo($articleResource, $selectedFields);
                $elem['resolved_data'] = $galleryItems;
            }

            // Mapa dinámico con origen "artículo actual"
            if ($elemType === 'map'
                && ($elem['content']['mode'] ?? '') === 'dynamic'
                && ($elem['content']['dynamic_binding']['source'] ?? '') === 'current_article'
            ) {
                $locationField = $elem['content']['dynamic_binding']['location_field'] ?? null;
                $marker = $this->mapearArticuloAMarcador($articleResource, $locationField, $elem['content'] ?? []);
                $elem['resolved_data'] = [
                    'markers' => $marker ? [$marker] : [],
                ];
            }

            // Sublayouts en plantilla de artículo
            if ($elemType === 'sublayout' && !empty($elem['content']['rows']) && is_array($elem['content']['rows'])) {
                foreach ($elem['content']['rows'] as &$subRow) {
                    if (empty($subRow['columns']) || !is_array($subRow['columns'])) continue;
                    foreach ($subRow['columns'] as &$subCol) {
                        if (!empty($subCol['elements']) && is_array($subCol['elements'])) {
                            $this->inyectarDatosArticuloEnElementos(
                                $subCol['elements'],
                                $navegacion,
                                $paginacionArticulos,
                                $articleResource
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Inyecta metadatos de paginación recursivamente en elementos pagination (incluyendo sublayouts).
     */
    private function inyectarPaginacionEnElementos(array &$elements, array $paginacionData): void
    {
        foreach ($elements as &$elem) {
            if (($elem['type'] ?? '') === 'pagination') {
                $elem['resolved_data'] = [
                    'paginacion' => $paginacionData,
                ];
            } elseif (($elem['type'] ?? '') === 'sublayout' && !empty($elem['content']['rows']) && is_array($elem['content']['rows'])) {
                foreach ($elem['content']['rows'] as &$subrow) {
                    foreach ($subrow['columns'] ?? [] as &$subcol) {
                        if (!empty($subcol['elements']) && is_array($subcol['elements'])) {
                            $this->inyectarPaginacionEnElementos($subcol['elements'], $paginacionData);
                        }
                    }
                }
            }
        }
    }

    /**
     * Genera la paginación numérica de artículos hermanos dentro de la categoría.
     * Cada número (1, 2, 3...) corresponde a un artículo de la misma categoría,
     * marcando como 'active' el artículo actual y evitando URLs erróneas.
     */
    public function generarPaginacionArticulosCategoria(Article $currentArticle, Category $category): array
    {
        $orderType = $category->articles_order ?? 'published_at_desc';

        $query = Article::publicados()
            ->where('category_id', $category->id)
            ->select(['id', 'title', 'slug', 'published_at', 'order']);

        switch ($orderType) {
            case 'published_at_asc':
                $query->orderBy('published_at')->orderBy('id');
                break;
            case 'title_asc':
                $query->orderBy('title')->orderBy('id');
                break;
            case 'title_desc':
                $query->orderByDesc('title')->orderByDesc('id');
                break;
            case 'manual':
                $query->orderBy('order')->orderBy('id');
                break;
            case 'published_at_desc':
            default:
                $query->orderByDesc('published_at')->orderByDesc('id');
                break;
        }

        $articles = $query->get();
        $total = $articles->count();

        if ($total === 0) {
            return [
                'links'             => [],
                'current_page'      => 1,
                'last_page'         => 1,
                'total'             => 0,
                'base_slug'         => $category->slug,
                'is_article_series' => true,
            ];
        }

        $currentIndex = $articles->search(fn ($a) => $a->id === $currentArticle->id);
        $currentPos = $currentIndex !== false ? $currentIndex + 1 : 1;

        $catSlug = ltrim($category->slug, '/');

        // Botón Anterior
        $prevArticle = ($currentIndex !== false && $currentIndex > 0) ? $articles->get($currentIndex - 1) : null;
        $prevUrl = $prevArticle ? url("/{$catSlug}/{$prevArticle->slug}") : null;

        // Botón Siguiente
        $nextArticle = ($currentIndex !== false && $currentIndex < $total - 1) ? $articles->get($currentIndex + 1) : null;
        $nextUrl = $nextArticle ? url("/{$catSlug}/{$nextArticle->slug}") : null;

        $links = [
            [
                'url'    => $prevUrl,
                'label'  => '&laquo; Anterior',
                'active' => false,
                'page'   => $prevArticle ? $currentPos - 1 : null,
            ],
        ];

        foreach ($articles as $idx => $art) {
            $pageNum = $idx + 1;
            $links[] = [
                'url'    => url("/{$catSlug}/{$art->slug}"),
                'label'  => (string) $pageNum,
                'active' => $art->id === $currentArticle->id,
                'page'   => $pageNum,
                'title'  => $art->title,
            ];
        }

        $links[] = [
            'url'    => $nextUrl,
            'label'  => 'Siguiente &raquo;',
            'active' => false,
            'page'   => $nextArticle ? $currentPos + 1 : null,
        ];

        return [
            'links'             => $links,
            'current_page'      => $currentPos,
            'last_page'         => $total,
            'total'             => $total,
            'base_slug'         => $category->slug,
            'is_article_series' => true,
        ];
    }

    /**
     * Resuelve elementos de paginación huérfanos o en páginas estáticas vinculándolos al grid de artículos.
     */
    private function resolverPaginacionGeneralSiExiste(array &$sections): void
    {
        $hayPaginacionSinResolver = false;
        $gridConfig = null;

        $recorrer = function (array $elements) use (&$recorrer, &$hayPaginacionSinResolver, &$gridConfig) {
            foreach ($elements as $el) {
                $type = $el['type'] ?? '';
                $elemMode = $el['content']['mode'] ?? 'archive_numeric';

                // Solo consideramos huérfanos elementos de paginación numérica que no tengan ya datos
                if ($type === 'pagination') {
                    $tieneNavegacion = !empty($el['resolved_data']['navegacion']);
                    $tienePaginacion = !empty($el['resolved_data']['paginacion']);
                    if ($elemMode !== 'article_nav' && !$tieneNavegacion && !$tienePaginacion) {
                        $hayPaginacionSinResolver = true;
                    }
                }
                if (($type === 'grid_articles' || $type === 'grid') && $gridConfig === null) {
                    $gridConfig = [
                        'category_id' => $el['content']['dynamic_binding']['category_id'] ?? $el['content']['category_id'] ?? 'all',
                        'limit'       => (int) ($el['content']['dynamic_binding']['limit'] ?? $el['content']['limit'] ?? 12),
                        'order_by'    => $el['content']['dynamic_binding']['order_by'] ?? $el['content']['order_by'] ?? 'published_at_desc',
                    ];
                }
                if ($type === 'sublayout' && !empty($el['content']['rows']) && is_array($el['content']['rows'])) {
                    foreach ($el['content']['rows'] as $sr) {
                        foreach ($sr['columns'] ?? [] as $sc) {
                            if (!empty($sc['elements']) && is_array($sc['elements'])) {
                                $recorrer($sc['elements']);
                            }
                        }
                    }
                }
            }
        };

        foreach ($sections as $sec) {
            foreach ($sec['rows'] ?? [] as $row) {
                foreach ($row['columns'] ?? [] as $col) {
                    if (!empty($col['elements']) && is_array($col['elements'])) {
                        $recorrer($col['elements']);
                    }
                }
            }
        }

        if (!$hayPaginacionSinResolver) {
            return;
        }

        $rawLimit = (int) ($gridConfig['limit'] ?? 12);
        $perPage = ($rawLimit > 0 && $rawLimit < 999) ? $rawLimit : 12;
        $catId = $gridConfig['category_id'] ?? 'all';
        $pageNumber = (int) (request()->route('page') ?? request('page', 1));
        if ($pageNumber < 1) $pageNumber = 1;

        $rawPath = trim(request()->path(), '/');
        $cleanPath = preg_replace('/\/pagina\/\d+$/i', '', $rawPath);

        // Si la ruta contiene '/' (ej. artículo categoria/articulo) o pertenece al panel,
        // no usarla como slug de paginación; usar la categoría configurada en el grid si existe
        if (str_contains($cleanPath, '/') || str_starts_with($cleanPath, 'panel')) {
            if ($catId !== 'all' && is_numeric($catId)) {
                $catModel = Category::find((int) $catId);
                $baseSlug = $catModel ? $catModel->slug : '';
            } else {
                $baseSlug = '';
            }
        } else {
            $baseSlug = $cleanPath;
        }

        $query = Article::publicados()
            ->with(['category:id,name,slug,extra_fields_schema', 'author:id,name']);

        if ($catId !== 'all' && is_numeric($catId)) {
            $query->where('category_id', (int) $catId);
        }

        $orderBy = $gridConfig['order_by'] ?? 'published_at_desc';
        match ($orderBy) {
            'order_asc', 'manual' => $query->orderBy('order', 'asc')->orderByDesc('id'),
            'published_at_asc'    => $query->orderBy('published_at', 'asc')->orderBy('id', 'asc'),
            'title_asc'           => $query->orderBy('title', 'asc'),
            'title_desc'          => $query->orderBy('title', 'desc'),
            default               => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $paginador = $query->paginate($perPage, ['*'], 'page', $pageNumber);
        $paginador->withPath($baseSlug ? url("/{$baseSlug}") : url('/'));

        $paginacionData = [
            'links'        => self::formatearLinksPaginacion($paginador, $baseSlug),
            'current_page' => $paginador->currentPage(),
            'last_page'    => $paginador->lastPage(),
            'total'        => $paginador->total(),
            'base_slug'    => $baseSlug,
        ];

        $inyectar = function (array &$elements) use (&$inyectar, $paginacionData, $paginador) {
            foreach ($elements as &$el) {
                $type = $el['type'] ?? '';
                if ($type === 'pagination') {
                    // No sobreescribir si ya tiene navegación secuencial de artículo
                    if (empty($el['resolved_data']['navegacion'])) {
                        $el['resolved_data'] = [
                            'paginacion' => $paginacionData,
                        ];
                    }
                }
                if ($type === 'grid_articles' || $type === 'grid') {
                    $el['resolved_data'] = ArticleResource::collection($paginador)->resolve();
                }
                if ($type === 'sublayout' && !empty($el['content']['rows']) && is_array($el['content']['rows'])) {
                    foreach ($el['content']['rows'] as &$sr) {
                        foreach ($sr['columns'] ?? [] as &$sc) {
                            if (!empty($sc['elements']) && is_array($sc['elements'])) {
                                $inyectar($sc['elements']);
                            }
                        }
                    }
                }
            }
        };

        foreach ($sections as $sIdx => $sec) {
            if (empty($sec['rows']) || !is_array($sec['rows'])) continue;
            foreach ($sec['rows'] as $rIdx => $row) {
                if (empty($row['columns']) || !is_array($row['columns'])) continue;
                foreach ($row['columns'] as $cIdx => $col) {
                    if (!empty($col['elements']) && is_array($col['elements'])) {
                        $inyectar($sections[$sIdx]['rows'][$rIdx]['columns'][$cIdx]['elements']);
                    }
                }
            }
        }
    }

    /**
     * Formatea los enlaces de la paginación a URLs amigables limpias para SEO (/slug o /slug/pagina/2).
     */
    public static function formatearLinksPaginacion(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginador, string $slug = ''): array
    {
        $slugLimpio = trim($slug, '/');
        $currentPage = $paginador->currentPage();
        $lastPage = $paginador->lastPage();

        return collect($paginador->linkCollection())->map(function ($link) use ($slugLimpio, $currentPage, $lastPage) {
            $label = (string) ($link['label'] ?? '');

            $isPrev = str_contains($label, 'Anterior') || str_contains($label, '&laquo;') || str_contains($label, 'Previous');
            $isNext = str_contains($label, 'Siguiente') || str_contains($label, '&raquo;') || str_contains($label, 'Next');
            $isNumeric = is_numeric($label);

            $pageNum = null;
            if ($isNumeric) {
                $pageNum = (int) $label;
            } elseif ($link['url'] && preg_match('/[?&]page=(\d+)/', $link['url'], $m)) {
                $pageNum = (int) $m[1];
            } elseif ($isPrev && $currentPage > 1) {
                $pageNum = $currentPage - 1;
            } elseif ($isNext && $currentPage < $lastPage) {
                $pageNum = $currentPage + 1;
            }

            $cleanUrl = null;
            if ($isPrev) {
                if ($currentPage <= 1) {
                    $cleanUrl = null;
                } elseif ($currentPage === 2) {
                    $cleanUrl = $slugLimpio ? url("/{$slugLimpio}") : url('/');
                } else {
                    $targetP = $currentPage - 1;
                    $cleanUrl = $slugLimpio ? url("/{$slugLimpio}/pagina/{$targetP}") : url("/pagina/{$targetP}");
                }
            } elseif ($isNext) {
                if ($currentPage >= $lastPage) {
                    $cleanUrl = null;
                } else {
                    $targetP = $currentPage + 1;
                    $cleanUrl = $slugLimpio ? url("/{$slugLimpio}/pagina/{$targetP}") : url("/pagina/{$targetP}");
                }
            } elseif ($isNumeric && $pageNum !== null) {
                $cleanUrl = $pageNum <= 1
                    ? ($slugLimpio ? url("/{$slugLimpio}") : url('/'))
                    : ($slugLimpio ? url("/{$slugLimpio}/pagina/{$pageNum}") : url("/pagina/{$pageNum}"));
            }

            return [
                'url'    => $cleanUrl,
                'label'  => $label,
                'active' => $isNumeric ? ($pageNum === $currentPage) : (bool) ($link['active'] ?? false),
                'page'   => $pageNum,
            ];
        })->toArray();
    }

    /**
     * Construye la lista unificada de páginas, plantillas y extensiones autónomas (ej. Descargas)
     * para la barra de navegación superior e intercepción de clics del Page Builder.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function construirPaginasListBuilder(): array
    {
        $pages = \App\Models\Page::select('id', 'title', 'slug', 'is_homepage')
            ->orderBy('title', 'asc')
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'unique_key'  => "page_{$p->id}",
                'title'       => $p->title,
                'slug'        => $p->slug,
                'is_homepage' => (bool) $p->is_homepage,
                'url'         => $p->is_homepage ? '/' : '/' . ltrim((string) $p->slug, '/'),
                'builder_url' => route('paginas.builder', $p->id),
                'type'        => 'page',
            ]);

        $templatesList = \App\Models\BuilderTemplate::select('id', 'name', 'type')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn ($t) => [
                'id'            => $t->id,
                'unique_key'    => "template_{$t->id}",
                'title'         => "{$t->name} (" . ($t->type === 'single_article' ? 'Artículo' : 'Categoría') . ')',
                'slug'          => "plantillas/{$t->id}",
                'url'           => route('plantillas.builder', $t->id),
                'builder_url'   => route('plantillas.builder', $t->id),
                'type'          => 'template',
                'template_type' => $t->type,
            ]);

        $extraEntries = collect();
        if (
            class_exists(\App\Modules\Downloads\Models\DownloadSetting::class) &&
            \Illuminate\Support\Facades\Route::has('downloads.admin.builder')
        ) {
            try {
                $dlSettings = \App\Modules\Downloads\Models\DownloadSetting::cached();
                $dlSlug = ltrim((string) ($dlSettings->slug ?: 'descargas'), '/');

                $extraEntries->push([
                    'id'          => 'downloads-catalog',
                    'unique_key'  => 'page_downloads-catalog',
                    'title'       => ($dlSettings->page_title ?: 'Descargas') . ' (Portada)',
                    'slug'        => $dlSlug,
                    'is_homepage' => false,
                    'url'         => '/' . $dlSlug,
                    'builder_url' => route('downloads.admin.builder'),
                    'type'        => 'page',
                ]);

                if (\Illuminate\Support\Facades\Route::has('downloads.admin.item-builder')) {
                    $extraEntries->push([
                        'id'            => 'downloads-item',
                        'unique_key'    => 'template_downloads-item',
                        'title'         => 'Ficha de Descarga (Plantilla Ítem)',
                        'slug'          => $dlSlug . '/ficha-item',
                        'url'           => route('downloads.admin.item-builder'),
                        'builder_url'   => route('downloads.admin.item-builder'),
                        'type'          => 'template',
                        'template_type' => 'single_download',
                    ]);
                }
            } catch (\Throwable) {
                // Silencioso si las tablas del módulo aún no están migradas
            }
        }

        return $pages->concat($extraEntries)->concat($templatesList)->values()->all();
    }

    /**
     * Obtiene la colección de descargas de muestra formateadas mediante API Resource
     * para previsualización reactiva y navegación entre fichas en el Page Builder.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function obtenerDescargasMuestraBuilder(): array
    {
        if (
            !class_exists(\App\Modules\Downloads\Models\DownloadItem::class) ||
            !class_exists(\App\Modules\Downloads\Http\Resources\DownloadItemPublicResource::class)
        ) {
            return [];
        }

        try {
            $req = request();

            return \App\Modules\Downloads\Models\DownloadItem::query()
                ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")
                ->orderBy('order', 'asc')
                ->take(30)
                ->get()
                ->map(function ($item) use ($req) {
                    $data = (new \App\Modules\Downloads\Http\Resources\DownloadItemPublicResource($item, true))->resolve($req);
                    $data['excerpt'] = $data['short_description'] ?? '';
                    $data['_source'] = 'downloads';

                    return $data;
                })
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Resuelve variables dinámicas `{download.*}` dentro del layout de una Ficha Individual de Descarga.
     */
    public function resolveForDownload(?array $layout, object $downloadItem, ?object $dlSettings = null): ?array
    {
        if (empty($layout) || !isset($layout['sections']) || !is_array($layout['sections'])) {
            return $layout;
        }

        $siteSettings = SiteSetting::cached();
        $deliveryPrefix = trim((string) config('downloads.delivery_prefix', 'download-vault'), '/');
        $catalogSlug = $dlSettings?->slug ?: (string) config('downloads.defaults.slug', 'descargas');
        $catalogTitle = $dlSettings?->page_title ?: 'Descargas';
        $catalogUrl = '/' . ltrim($catalogSlug, '/');
        $itemUrl = $catalogUrl . '/' . ltrim((string) $downloadItem->slug, '/');
        $downloadUrl = (method_exists($downloadItem, 'hasFile') && $downloadItem->hasFile())
            ? url("/{$deliveryPrefix}/{$downloadItem->slug}/request")
            : '#';

        $escCatTitle = e($catalogTitle);
        $breadcrumbHtml = '<nav aria-label="Breadcrumb" class="text-sm text-zinc-500 dark:text-zinc-400">'
            . '<a href="/" class="hover:text-[var(--primary-color,#10b981)] transition-colors no-underline text-inherit">Inicio</a>'
            . '<span class="mx-2 opacity-60">/</span>'
            . '<a href="' . e($catalogUrl) . '" class="hover:text-[var(--primary-color,#10b981)] transition-colors no-underline text-inherit font-medium">' . $escCatTitle . '</a>'
            . '</nav>';

        $tokens = [
            '{download.title}'             => (string) ($downloadItem->title ?? ''),
            '{download.subtitle}'          => (string) ($downloadItem->subtitle ?? ''),
            '{download.short_description}' => (string) ($downloadItem->short_description ?? ''),
            '{download.excerpt}'           => (string) ($downloadItem->short_description ?? ''),
            '{download.body}'              => (string) ($downloadItem->body ?? ''),
            '{download.content}'           => (string) ($downloadItem->body ?? ''),
            '{download.featured_image}'    => (string) ($downloadItem->featured_image ?? ''),
            '{download.image}'             => (string) ($downloadItem->featured_image ?? ''),
            '{download.icon}'              => (string) ($downloadItem->icon ?: 'pi pi-box'),
            '{download.version}'           => (string) ($downloadItem->version ?? ''),
            '{download.compatibility}'     => (string) ($downloadItem->compatibility ?? ''),
            '{download.changelog}'         => nl2br(e((string) ($downloadItem->changelog ?? ''))),
            '{download.price}'             => method_exists($downloadItem, 'formattedPrice') ? $downloadItem->formattedPrice() : 'Gratis',
            '{download.file_size}'         => method_exists($downloadItem, 'formattedFileSize') ? $downloadItem->formattedFileSize() : '',
            '{download.license}'           => (string) ($downloadItem->license_type ?? ''),
            '{download.demo_url}'          => (string) ($downloadItem->demo_url ?? ''),
            '{download.documentation_url}' => (string) ($downloadItem->documentation_url ?? ''),
            '{download.url}'               => $itemUrl,
            '{download.download_url}'      => $downloadUrl,
            '{download.catalog_title}'     => $catalogTitle,
            '{download.catalog_url}'       => $catalogUrl,
            '{download.breadcrumb}'        => $breadcrumbHtml,
            '{site.name}'                  => (string) ($siteSettings->site_name ?? ''),
        ];

        $replaceRecursive = function (mixed &$node) use (&$replaceRecursive, $tokens): void {
            if (is_string($node)) {
                $node = strtr($node, $tokens);
                return;
            }
            if (is_array($node)) {
                foreach ($node as $key => &$val) {
                    if ($key === 'resolved_data') {
                        continue;
                    }
                    $replaceRecursive($val);
                }
                unset($val);
            }
        };

        $cloned = $layout;
        $replaceRecursive($cloned);

        $excludeDownloadId = isset($downloadItem->id) && is_numeric($downloadItem->id)
            ? (int) $downloadItem->id
            : null;

        return $this->resolve($cloned, null, true, $excludeDownloadId);
    }
}


