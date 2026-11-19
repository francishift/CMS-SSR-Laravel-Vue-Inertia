<?php

declare(strict_types=1);

namespace App\Modules\Downloads\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;
use App\Models\Article;
use App\Models\BuilderTemplate;
use App\Models\Category;
use App\Models\Page;
use App\Modules\Downloads\Http\Resources\DownloadItemPublicResource;
use App\Modules\Downloads\Models\DownloadItem;
use App\Modules\Downloads\Models\DownloadSetting;
use App\Services\CacheInvalidationService;
use App\Services\PageBuilderResolverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador del Constructor Visual (Page Builder) para la Portada del Catálogo de Descargas.
 *
 * Reutiliza la interfaz `Paginas/Builder` del CMS sin duplicar componentes, pre-cargando
 * una estructura inicial limpia con la cabecera y el Grid dinámico de Descargas si aún no existe.
 */
class AdminDownloadBuilderController extends Controller
{
    public function __construct(
        private readonly PageBuilderResolverService $resolverService,
    ) {}

    /**
     * Abre la portada de Descargas dentro del Page Builder visual.
     */
    public function edit(Request $request): Response
    {
        $dlSettings = DownloadSetting::cached();
        $savedLayout = $dlSettings->layout_json;
        $rawLayout = (is_array($savedLayout) && !empty($savedLayout['sections']))
            ? $this->normalizarLayoutDescargas($savedLayout)
            : $this->generarLayoutInicial($dlSettings);

        [$categories, $articles, $descargasMuestra, $paginasList] = $this->cargarContextoBuilder($request);

        return Inertia::render('Paginas/Builder', [
            'pagina' => [
                'id'              => 'downloads-catalog',
                'title'           => $dlSettings->page_title ?: 'Descargas',
                'slug'            => $dlSettings->slug ?: 'descargas',
                'back_url'        => route('downloads.admin.index'),
                'layout_json'     => $rawLayout,
                'seo_title'       => $dlSettings->seo_title,
                'seo_description' => $dlSettings->seo_description,
                'seo_og_image'    => $dlSettings->seo_og_image,
                'meta_robots'     => $dlSettings->meta_robots,
            ],
            'categorias'         => CategoryResource::collection($categories)->resolve(),
            'articulosMuestra'   => ArticleResource::collection($articles)->resolve(),
            'descargasMuestra'   => $descargasMuestra,
            'paginasList'        => $paginasList,
            'rutaGuardarBuilder' => route('downloads.admin.builder.update'),
            'rutaPreviewFrame'   => route('downloads.admin.builder.preview-frame'),
        ]);
    }

    /**
     * Entrega el iframe aislado de previsualización en vivo del catálogo de Descargas.
     */
    public function previewFrame(Request $request): Response
    {
        $dlSettings = DownloadSetting::cached();
        $savedLayout = $dlSettings->layout_json;
        $rawLayout = (is_array($savedLayout) && !empty($savedLayout['sections']))
            ? $this->normalizarLayoutDescargas($savedLayout)
            : $this->generarLayoutInicial($dlSettings);

        $resolvedLayout = $this->resolverService->resolve($rawLayout);
        [$categories, $articles, $descargasMuestra] = $this->cargarContextoBuilder($request);

        return Inertia::render('Paginas/BuilderPreviewFrame', [
            'pagina' => [
                'id'          => 'downloads-catalog',
                'title'       => $dlSettings->page_title ?: 'Descargas',
                'slug'        => $dlSettings->slug ?: 'descargas',
                'layout_json' => $resolvedLayout,
            ],
            'initialLayout'    => $resolvedLayout,
            'categorias'       => CategoryResource::collection($categories)->resolve(),
            'articulosMuestra' => $articles ? ArticleResource::collection($articles)->resolve() : [],
            'descargasMuestra' => $descargasMuestra,
        ]);
    }

    /**
     * Guarda la estructura maquetada en `DownloadSetting`, activa `use_builder_layout` e invalida Redis.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'layout_json'     => ['nullable', 'array'],
            'seo_title'       => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'seo_og_image'    => ['nullable', 'string', 'max:2048'],
            'meta_robots'     => ['nullable', 'string', 'max:60'],
        ]);

        $layoutJson = $validated['layout_json'] ?? null;
        if (is_array($layoutJson)) {
            array_walk_recursive($layoutJson, static function (&$value) {
                if (is_string($value)) {
                    $value = str_replace(['&nbsp;', "\xC2\xA0", "\xC2\xAD", '&shy;'], [' ', ' ', '', ''], $value);
                }
            });
            $layoutJson = $this->normalizarLayoutDescargas($layoutJson);
        }

        $settings = DownloadSetting::query()->first() ?? new DownloadSetting(DownloadSetting::defaultAttributes());
        $hasSections = !empty($layoutJson['sections']) && is_array($layoutJson['sections']);

        $settings->fill([
            'layout_json'        => $layoutJson,
            'use_builder_layout' => $hasSections,
            'seo_title'          => $validated['seo_title'] ?? $settings->seo_title,
            'seo_description'    => $validated['seo_description'] ?? $settings->seo_description,
            'seo_og_image'       => $validated['seo_og_image'] ?? $settings->seo_og_image,
            'meta_robots'        => array_key_exists('meta_robots', $validated) ? $validated['meta_robots'] : $settings->meta_robots,
        ]);
        $settings->save();

        DownloadSetting::clearCache();
        CacheInvalidationService::limpiarCachePublica();

        if (function_exists('activity')) {
            activity('maquetador')
                ->causedBy($request->user())
                ->performedOn($settings)
                ->event('updated')
                ->log('Actualizó maquetación visual de la Portada de Descargas en Page Builder');
        }

        return back()->with('success', 'Diseño visual de Descargas guardado y activado correctamente.');
    }

    /**
     * Abre la Plantilla Visual de Ficha Individual de Descarga dentro del Page Builder.
     */
    public function editItemTemplate(Request $request): Response
    {
        $dlSettings = DownloadSetting::cached();
        $savedItemLayout = $dlSettings->item_layout_json;
        $rawItemLayout = (is_array($savedItemLayout) && !empty($savedItemLayout['sections']))
            ? $this->normalizarLayoutDescargas($savedItemLayout)
            : $this->generarLayoutInicialItem();

        [$categories, $articles, $descargasMuestra, $paginasList] = $this->cargarContextoBuilder($request);

        $previewDownloadId = $request->query('preview_download_id');
        $sampleItem = $this->resolverItemMuestra($previewDownloadId);
        $sampleResolved = $sampleItem
            ? (new DownloadItemPublicResource($sampleItem, true))->resolve($request)
            : ($descargasMuestra[0] ?? null);

        $previewParams = [];
        if ($sampleItem) {
            $previewParams['preview_download_id'] = $sampleItem->id;
        }

        return Inertia::render('Paginas/Builder', [
            'pagina' => [
                'id'          => 'downloads-item',
                'title'       => 'Ficha de Descarga (Plantilla Ítem)',
                'slug'        => ($dlSettings->slug ?: 'descargas') . '/ficha-item',
                'type'        => 'single_download',
                'back_url'    => route('downloads.admin.index'),
                'layout_json' => $rawItemLayout,
            ],
            'esPlantilla'        => true,
            'tipoPlantilla'      => 'single_download',
            'descargaMuestra'    => $sampleResolved,
            'descargasMuestra'   => $descargasMuestra,
            'categorias'         => CategoryResource::collection($categories)->resolve(),
            'articulosMuestra'   => ArticleResource::collection($articles)->resolve(),
            'paginasList'        => $paginasList,
            'rutaGuardarBuilder' => route('downloads.admin.item-builder.update'),
            'rutaPreviewFrame'   => route('downloads.admin.item-builder.preview-frame', $previewParams),
        ]);
    }

    /**
     * Marco aislado de previsualización en vivo para la Plantilla de Ficha Individual de Descarga.
     */
    public function previewFrameItemTemplate(Request $request): Response
    {
        $dlSettings = DownloadSetting::cached();
        $savedItemLayout = $dlSettings->item_layout_json;
        $rawItemLayout = (is_array($savedItemLayout) && !empty($savedItemLayout['sections']))
            ? $this->normalizarLayoutDescargas($savedItemLayout)
            : $this->generarLayoutInicialItem();

        [$categories, $articles, $descargasMuestra] = $this->cargarContextoBuilder($request);

        $previewDownloadId = $request->query('preview_download_id');
        $sampleItem = $this->resolverItemMuestra($previewDownloadId);
        $resolvedLayout = $sampleItem
            ? $this->resolverService->resolveForDownload($rawItemLayout, $sampleItem, $dlSettings)
            : $this->resolverService->resolve($rawItemLayout);

        $sampleResolved = $sampleItem
            ? (new DownloadItemPublicResource($sampleItem, true))->resolve($request)
            : ($descargasMuestra[0] ?? null);

        return Inertia::render('Paginas/BuilderPreviewFrame', [
            'pagina' => [
                'id'          => 'downloads-item',
                'title'       => 'Ficha de Descarga (Plantilla Ítem)',
                'slug'        => ($dlSettings->slug ?: 'descargas') . '/ficha-item',
                'layout_json' => $resolvedLayout,
            ],
            'initialLayout'     => $resolvedLayout,
            'esPlantilla'       => true,
            'tipoPlantilla'     => 'single_download',
            'descargaMuestra'   => $sampleResolved,
            'descargasMuestra'  => $descargasMuestra,
            'catalogoDescargas' => (new \App\Modules\Downloads\Http\Resources\DownloadSettingResource($dlSettings))->resolve($request),
            'categorias'        => CategoryResource::collection($categories)->resolve(),
            'articulosMuestra'  => $articles ? ArticleResource::collection($articles)->resolve() : [],
        ]);
    }

    /**
     * Guarda la estructura visual de la Plantilla de Ficha Individual de Descarga.
     */
    public function updateItemTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'layout_json' => ['nullable', 'array'],
        ]);

        $layoutJson = $validated['layout_json'] ?? null;
        if (is_array($layoutJson)) {
            array_walk_recursive($layoutJson, static function (&$value) {
                if (is_string($value)) {
                    $value = str_replace(['&nbsp;', "\xC2\xA0", "\xC2\xAD", '&shy;'], [' ', ' ', '', ''], $value);
                }
            });
            $layoutJson = $this->normalizarLayoutDescargas($layoutJson);
        }

        $settings = DownloadSetting::query()->first() ?? new DownloadSetting(DownloadSetting::defaultAttributes());
        $hasSections = is_array($layoutJson) && !empty($layoutJson['sections']);

        $settings->fill([
            'item_layout_json'        => $layoutJson,
            'use_item_builder_layout' => $hasSections,
        ]);
        $settings->save();

        DownloadSetting::clearCache();
        CacheInvalidationService::limpiarCachePublica();

        if (function_exists('activity')) {
            activity('maquetador')
                ->causedBy($request->user())
                ->performedOn($settings)
                ->event('updated')
                ->log('Actualizó plantilla visual de Ficha Individual de Descarga en Page Builder');
        }

        return back()->with('success', 'Plantilla visual de Ficha de Descarga guardada y activada.');
    }

    /**
     * Estructura inicial preconfigurada con Cabecera Editorial + Catálogo Rico de Descargas.
     *
     * @return array<string, mixed>
     */
    public function generarLayoutInicial(DownloadSetting $dlSettings): array
    {
        $elementsHeader = [
            [
                'id'          => 'el_dl_h1',
                'type'        => 'heading',
                'name'        => 'Título de Descargas',
                'admin_label' => 'Título de Descargas',
                'content'     => [
                    'text'       => $dlSettings->page_title ?: 'Descargas',
                    'tag'        => 'h1',
                    'size'       => $dlSettings->header_title_size ?: 'h1',
                    'align'      => $dlSettings->header_align ?: 'left',
                    'decoration' => 'divider',
                ],
            ],
        ];

        if (!empty($dlSettings->description)) {
            $elementsHeader[] = [
                'id'          => 'el_dl_desc',
                'type'        => 'text',
                'name'        => 'Descripción de Portada',
                'admin_label' => 'Descripción de Portada',
                'content'     => [
                    'html'         => '<p>' . e($dlSettings->description) . '</p>',
                    'style_preset' => 'lead',
                    'margin_top'   => 'md',
                    'align'        => $dlSettings->header_align ?: 'left',
                ],
            ];
        }

        return [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'          => 'sec_dl_header',
                    'name'        => 'Cabecera de Descargas',
                    'admin_label' => 'Cabecera de Descargas',
                    'settings'    => [
                        'container'             => $dlSettings->container_width ?: 'boxed',
                        'padding'               => 'md',
                        'margin'                => 'none',
                        'padding_top'           => 'lg',
                        'padding_bottom'        => 'sm',
                        'padding_top_tablet'    => 'md',
                        'padding_bottom_tablet' => 'sm',
                        'padding_top_mobile'    => 'md',
                        'padding_bottom_mobile' => 'sm',
                        'bg_preset'             => 'transparent',
                        'animation'             => 'slide_top_small',
                    ],
                    'rows' => [
                        [
                            'id'      => 'row_dl_header',
                            'layout'  => '1',
                            'gap'     => 'md',
                            'align'   => 'start',
                            'columns' => [
                                [
                                    'id'       => 'col_dl_header',
                                    'width'    => [
                                        'xs' => '1/1',
                                        'sm' => 'inherit',
                                        'md' => 'inherit',
                                        'lg' => ($dlSettings->header_align === 'center') ? '1/1' : '2/3',
                                        'xl' => 'inherit',
                                    ],
                                    'order'    => ['xs' => 'none', 'sm' => 'none', 'md' => 'none', 'lg' => 'none', 'xl' => 'none'],
                                    'elements' => $elementsHeader,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id'          => 'sec_dl_grid',
                    'name'        => 'Catálogo Dinámico de Descargas',
                    'admin_label' => 'Catálogo Dinámico de Descargas',
                    'settings'    => [
                        'container'       => $dlSettings->container_width ?: 'boxed',
                        'padding'         => 'md',
                        'margin'          => 'none',
                        'padding_top'     => 'sm',
                        'padding_bottom'  => 'lg',
                        'bg_preset'       => 'transparent',
                        'animation'       => 'slide_top_small',
                        'animation_delay' => 100,
                    ],
                    'rows' => [
                        [
                            'id'        => 'row_dl_grid',
                            'layout'    => '1',
                            'container' => 'inherit',
                            'gap'       => 'md',
                            'align'     => 'start',
                            'columns'   => [
                                [
                                    'id'       => 'col_dl_grid',
                                    'width'    => ['xs' => '1/1', 'sm' => 'inherit', 'md' => 'inherit', 'lg' => '1/1', 'xl' => 'inherit'],
                                    'order'    => ['xs' => 'none', 'sm' => 'none', 'md' => 'none', 'lg' => 'none', 'xl' => 'none'],
                                    'elements' => [
                                        [
                                            'id'          => 'el_dl_grid_items',
                                            'type'        => 'grid_articles',
                                            'name'        => 'Catálogo de Descargas',
                                            'admin_label' => 'Catálogo de Descargas',
                                            'content'     => [
                                                'mode'                => 'dynamic',
                                                'download_card_mode'  => 'download_card',
                                                'columns_desktop'     => (int) ($dlSettings->cols_desktop ?: 3),
                                                'columns_tablet'      => (string) ($dlSettings->cols_tablet ?: '2'),
                                                'columns_mobile'      => (string) ($dlSettings->cols_mobile ?: '1'),
                                                'gap'                 => $dlSettings->gap_size ?: 'md',
                                                'card_style'          => $dlSettings->card_style ?: 'default',
                                                'text_align'          => $dlSettings->card_text_align ?: 'left',
                                                'title_tag'           => $dlSettings->item_title_tag ?: 'h2',
                                                'title_size'          => $dlSettings->item_title_size ?: 'h4',
                                                'text_size'           => $dlSettings->item_desc_size ?: 'sm',
                                                'desc_lines'          => $dlSettings->item_desc_lines ?: '3',
                                                'image_ratio'         => $dlSettings->item_image_ratio ?: '16:9',
                                                'svg_size'            => $dlSettings->item_svg_size ?: 'lg',
                                                'image_fit'           => 'contain',
                                                'image_position'      => $dlSettings->item_image_position ?: 'flush',
                                                'button_style'        => $dlSettings->item_button_style ?: 'button_primary',
                                                'button_size'         => $dlSettings->item_button_size ?: 'md',
                                                'button_text'         => 'Ver ficha',
                                                'show_quick_download' => (bool) ($dlSettings->show_quick_download ?? true),
                                                'show_meta'           => (bool) ($dlSettings->show_item_meta ?? true),
                                                'show_badge'          => (bool) ($dlSettings->show_item_badges ?? true),
                                                'show_image'          => (bool) ($dlSettings->show_item_image ?? true),
                                                'show_description'    => (bool) ($dlSettings->show_item_description ?? true),
                                                'show_icon'           => false,
                                                'dynamic_binding'     => [
                                                    'source'   => 'downloads',
                                                    'order_by' => 'order_asc',
                                                    'limit'    => (int) ($dlSettings->per_page ?: 12),
                                                ],
                                                'dynamic_mapping'     => [
                                                    'title_field'       => 'title',
                                                    'description_field' => 'short_description',
                                                    'badge_field'       => 'version_price',
                                                    'image_field'       => 'featured_image',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Normaliza estructuras heredadas (`type: grid` -> `grid_articles`, `layout: 1/1` -> `1`).
     *
     * @param  array<string, mixed>  $layout
     * @return array<string, mixed>
     */
    private function normalizarLayoutDescargas(array $layout): array
    {
        if (empty($layout['sections']) || !is_array($layout['sections'])) {
            return $layout;
        }

        foreach ($layout['sections'] as &$section) {
            foreach ($section['rows'] ?? [] as &$row) {
                if (($row['layout'] ?? '') === '1/1') {
                    $row['layout'] = '1';
                }
                foreach ($row['columns'] ?? [] as &$col) {
                    foreach ($col['elements'] ?? [] as &$elem) {
                        if (($elem['type'] ?? '') === 'grid') {
                            $elem['type'] = 'grid_articles';
                        }
                        unset($elem['resolved_data']);
                    }
                    unset($elem);
                }
                unset($col);
            }
            unset($row);
        }
        unset($section);

        return $layout;
    }

    /**
     * Plantilla inicial editable para la columna principal (8/12) de la Ficha Individual de Descarga,
     * utilizando variables dinámicas `{download.*}` idénticas en filosofía a `{article.*}`.
     *
     * @return array<string, mixed>
     */
    public function generarLayoutInicialItem(): array
    {
        return [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'          => 'sec_dl_item_main',
                    'name'        => 'Contenido Principal de la Ficha',
                    'admin_label' => 'Contenido Principal de la Ficha',
                    'settings'    => [
                        'container'      => 'full_bleed',
                        'no_h_padding'   => true,
                        'padding'        => 'none',
                        'padding_top'    => 'none',
                        'padding_bottom' => 'none',
                        'margin'         => 'none',
                        'bg_preset'      => 'transparent',
                        'animation'      => 'slide_top_small',
                    ],
                    'rows' => [
                        [
                            'id'      => 'row_dl_item_header',
                            'layout'  => '1',
                            'gap'     => 'sm',
                            'align'   => 'start',
                            'columns' => [
                                [
                                    'id'       => 'col_dl_item_header',
                                    'width'    => ['xs' => '1/1', 'sm' => 'inherit', 'md' => 'inherit', 'lg' => '1/1', 'xl' => 'inherit'],
                                    'order'    => ['xs' => 'none', 'sm' => 'none', 'md' => 'none', 'lg' => 'none', 'xl' => 'none'],
                                    'elements' => [
                                        [
                                            'id'          => 'el_dl_item_meta',
                                            'type'        => 'icon',
                                            'name'        => 'Compatibilidad y Versión',
                                            'admin_label' => 'Compatibilidad y Versión',
                                            'content'     => [
                                                'icon'            => '{download.icon}',
                                                'text'            => '{download.compatibility} · v{download.version}',
                                                'icon_size'       => 'xs',
                                                'icon_style_mode' => 'soft_bg',
                                                'icon_color'      => 'primary',
                                                'icon_bg_color'   => 'primary_soft',
                                                'text_size'       => 'xs',
                                                'text_weight'     => 'semibold',
                                                'margin_bottom'   => 'xs',
                                            ],
                                        ],
                                        [
                                            'id'          => 'el_dl_item_h1',
                                            'type'        => 'heading',
                                            'name'        => 'Título y Subtítulo del Plugin',
                                            'admin_label' => 'Título y Subtítulo del Plugin',
                                            'content'     => [
                                                'text'              => '{download.title}',
                                                'subtitle'          => '{download.subtitle}',
                                                'subtitle_position' => 'below',
                                                'tag'               => 'h1',
                                                'size'              => 'h1',
                                                'align'             => 'left',
                                                'decoration'        => 'divider',
                                                'margin_bottom'     => 'sm',
                                            ],
                                        ],
                                        [
                                            'id'          => 'el_dl_item_lead',
                                            'type'        => 'text',
                                            'name'        => 'Resumen de Entrada (Lead)',
                                            'admin_label' => 'Resumen de Entrada (Lead)',
                                            'content'     => [
                                                'html'          => '<p>{download.short_description}</p>',
                                                'style_preset'  => 'lead',
                                                'margin_top'    => 'sm',
                                                'margin_bottom' => 'md',
                                            ],
                                        ],
                                        [
                                            'id'          => 'el_dl_item_img',
                                            'type'        => 'image',
                                            'name'        => 'Ilustración / Icono SVG',
                                            'admin_label' => 'Ilustración / Icono SVG',
                                            'content'     => [
                                                'src'            => '{download.featured_image}',
                                                'alt'            => '{download.title}',
                                                'align'          => 'center',
                                                'max_width_px'   => 128,
                                                'color_filter'   => 'none',
                                                'custom_classes' => 'w-full py-8 px-6 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/70 flex justify-center [&_img]:w-28 [&_img]:h-28 [&_img]:object-contain [&_img]:dark:invert [&_img]:dark:opacity-90',
                                            ],
                                        ],
                                        [
                                            'id'          => 'el_dl_item_body',
                                            'type'        => 'text',
                                            'name'        => 'Descripción Detallada e Instrucciones',
                                            'admin_label' => 'Descripción Detallada e Instrucciones',
                                            'content'     => [
                                                'html'          => '<div>{download.body}</div>',
                                                'margin_top'    => 'md',
                                                'margin_bottom' => 'md',
                                            ],
                                        ],
                                        [
                                            'id'          => 'el_dl_item_changelog',
                                            'type'        => 'text',
                                            'name'        => 'Historial de Cambios (Changelog)',
                                            'admin_label' => 'Historial de Cambios (Changelog)',
                                            'content'     => [
                                                'html'           => '<h3>Historial de cambios</h3><p class="font-mono text-xs">{download.changelog}</p>',
                                                'margin_top'     => 'md',
                                                'margin_bottom'  => 'none',
                                                'custom_classes' => 'rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-900/60 p-6',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id'          => 'sec_dl_item_related',
                    'name'        => 'Otros Recursos Relacionados',
                    'admin_label' => 'Otros Recursos Relacionados',
                    'settings'    => [
                        'container'      => 'boxed',
                        'padding'        => 'md',
                        'padding_top'    => 'md',
                        'padding_bottom' => 'lg',
                        'margin'         => 'none',
                        'bg_preset'      => 'muted',
                        'animation'      => 'slide_top_small',
                    ],
                    'rows' => [
                        [
                            'id'      => 'row_dl_item_rel_head',
                            'layout'  => '1/2+1/2',
                            'gap'     => 'sm',
                            'align'   => 'center',
                            'columns' => [
                                [
                                    'id'       => 'col_dl_item_rel_h2',
                                    'width'    => ['xs' => '1/1', 'sm' => 'expand', 'md' => 'inherit', 'lg' => 'expand', 'xl' => 'inherit'],
                                    'order'    => ['xs' => 'none', 'sm' => 'none', 'md' => 'none', 'lg' => 'none', 'xl' => 'none'],
                                    'elements' => [
                                        [
                                            'id'          => 'el_dl_item_rel_h2',
                                            'type'        => 'heading',
                                            'name'        => 'Título Relacionados',
                                            'admin_label' => 'Título Relacionados',
                                            'content'     => [
                                                'text'          => 'Más en {download.catalog_title}',
                                                'tag'           => 'h2',
                                                'size'          => 'h3',
                                                'align'         => 'left',
                                                'decoration'    => 'divider',
                                                'margin_bottom' => 'xs',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'id'       => 'col_dl_item_rel_cta',
                                    'width'    => ['xs' => '1/1', 'sm' => 'auto', 'md' => 'inherit', 'lg' => 'auto', 'xl' => 'inherit'],
                                    'order'    => ['xs' => 'none', 'sm' => 'none', 'md' => 'none', 'lg' => 'none', 'xl' => 'none'],
                                    'elements' => [
                                        [
                                            'id'          => 'el_dl_item_rel_btn',
                                            'type'        => 'button',
                                            'name'        => 'Enlace Ver Catálogo',
                                            'admin_label' => 'Enlace Ver Catálogo',
                                            'content'     => [
                                                'label'         => 'Ver todo el catálogo',
                                                'url'           => '{download.catalog_url}',
                                                'variant'       => 'link',
                                                'size'          => 'md',
                                                'icon'          => 'pi pi-arrow-right',
                                                'icon_position' => 'right',
                                                'align'         => 'right',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id'      => 'row_dl_item_rel_grid',
                            'layout'  => '1',
                            'gap'     => 'md',
                            'align'   => 'start',
                            'columns' => [
                                [
                                    'id'       => 'col_dl_item_rel_grid',
                                    'width'    => ['xs' => '1/1', 'sm' => 'inherit', 'md' => 'inherit', 'lg' => '1/1', 'xl' => 'inherit'],
                                    'order'    => ['xs' => 'none', 'sm' => 'none', 'md' => 'none', 'lg' => 'none', 'xl' => 'none'],
                                    'elements' => [
                                        [
                                            'id'          => 'el_dl_item_rel_grid',
                                            'type'        => 'grid_articles',
                                            'name'        => 'Grid de Otras Descargas',
                                            'admin_label' => 'Grid de Otras Descargas',
                                            'content'     => [
                                                'mode'               => 'dynamic',
                                                'download_card_mode' => 'download_card',
                                                'columns'            => 3,
                                                'columns_desktop'    => 3,
                                                'columns_tablet'     => '2',
                                                'columns_mobile'     => '1',
                                                'gap'                => 'md',
                                                'show_quick_download'=> true,
                                                'order_by'           => 'order_asc',
                                                'dynamic_binding'    => [
                                                    'source'   => 'downloads',
                                                    'order_by' => 'order_asc',
                                                    'limit'    => 3,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function resolverItemMuestra(mixed $previewDownloadId): ?DownloadItem
    {
        if ($previewDownloadId && is_numeric($previewDownloadId)) {
            $found = DownloadItem::query()->find((int) $previewDownloadId);
            if ($found) {
                return $found;
            }
        }

        return DownloadItem::query()
            ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")
            ->orderBy('order', 'asc')
            ->first();
    }

    /**
     * @return array{0: mixed, 1: mixed, 2: array<int, array<string, mixed>>, 3: array<int, array<string, mixed>>}
     */
    private function cargarContextoBuilder(Request $request): array
    {
        $categories = Category::activas()
            ->select('id', 'name', 'slug', 'description', 'featured_image', 'icon', 'color', 'order', 'extra_fields_schema', 'category_builder_template_id', 'article_builder_template_id')
            ->orderBy('order', 'asc')
            ->get();

        $articles = Article::publicados()
            ->select('id', 'title', 'slug', 'excerpt', 'body', 'featured_image', 'icon', 'order', 'category_id', 'author_id', 'published_at', 'reading_time', 'extra_fields_data', 'article_builder_template_id')
            ->with(['category:id,name,slug,extra_fields_schema,category_builder_template_id,article_builder_template_id', 'author:id,name'])
            ->orderByDesc('published_at')
            ->take(30)
            ->get();

        $descargasMuestra = PageBuilderResolverService::obtenerDescargasMuestraBuilder();
        $paginasList = PageBuilderResolverService::construirPaginasListBuilder();

        return [$categories, $articles, $descargasMuestra, $paginasList];
    }
}
