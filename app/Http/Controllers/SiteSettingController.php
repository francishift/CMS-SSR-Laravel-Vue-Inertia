<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSiteSettingRequest;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\SiteSettingResource;
use App\Models\Article;
use App\Models\BuilderTemplate;
use App\Models\Category;
use App\Models\Page;
use App\Services\CacheInvalidationService;
use App\Services\PageBuilderResolverService;
use App\Services\SiteSettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la configuración global del sitio y maquetación del Pie de Página.
 */
class SiteSettingController extends Controller
{
    public function __construct(
        private readonly SiteSettingService $service,
    ) {}

    /**
     * Mostrar formulario de configuración.
     */
    public function index(): Response
    {
        return Inertia::render('Configuracion/Index', [
            'configuracion'        => new SiteSettingResource($this->service->obtener()),
            'fuentesDisponibles'   => array_values(config('cms.fuentes_disponibles', [])),
            'plantillasDisponibles' => config('cms.plantillas', []),
        ]);
    }

    /**
     * Guardar cambios en la configuración.
     */
    public function update(UpdateSiteSettingRequest $request)
    {
        $validated = $request->validated();

        if (array_key_exists('header_buttons', $validated) && is_array($validated['header_buttons'])) {
            $validated['header_cta_enable'] = false;
            $validated['header_cta_text'] = null;
        }

        $this->service->actualizar($validated);

        return redirect()->route('configuracion.index')
            ->with('success', 'Configuración actualizada correctamente.');
    }

    /**
     * Mostrar la interfaz del Page Builder para maquetar el Pie de Página (Footer).
     */
    public function editFooterBuilder(): Response
    {
        $settings = $this->service->obtener();
        $categories = Category::activas()
            ->select('id', 'name', 'slug', 'description', 'featured_image', 'icon', 'color', 'order', 'extra_fields_schema', 'category_builder_template_id', 'article_builder_template_id')
            ->orderBy('order', 'asc')
            ->get();

        $articles = Article::publicados()
            ->select('id', 'title', 'slug', 'excerpt', 'body', 'featured_image', 'icon', 'order', 'category_id', 'author_id', 'published_at', 'reading_time', 'extra_fields_data', 'article_builder_template_id')
            ->with(['category:id,name,slug,extra_fields_schema', 'author:id,name'])
            ->orderByDesc('published_at')
            ->take(50)
            ->get();

        $paginasList = \App\Services\PageBuilderResolverService::construirPaginasListBuilder();

        $defaultFooterLayout = [
            'version' => '1.0',
            'sections' => [
                [
                    'id' => 'sec_footer',
                    'name' => 'Sección Pie de Página',
                    'settings' => [
                        'container' => 'boxed',
                        'padding_top' => 'md',
                        'padding_bottom' => 'md',
                        'bg_preset' => 'zinc_900',
                        'text_color' => 'light',
                    ],
                    'rows' => [
                        [
                            'id' => 'row_footer',
                            'layout' => '1/3+1/3+1/3',
                            'columns' => [
                                [
                                    'id' => 'col_f1',
                                    'elements' => [
                                        [
                                            'id' => 'el_f_heading',
                                            'type' => 'heading',
                                            'content' => ['text' => $settings->site_name, 'tag' => 'h4', 'size' => 'h4']
                                        ],
                                        [
                                            'id' => 'el_f_text',
                                            'type' => 'text',
                                            'content' => ['html' => '<p>' . ($settings->site_description ?? '') . '</p>']
                                        ]
                                    ]
                                ],
                                [
                                    'id' => 'col_f2',
                                    'elements' => [
                                        [
                                            'id' => 'el_f_menu',
                                            'type' => 'menu',
                                            'content' => ['source' => 'location_header', 'orientation' => 'vertical', 'size' => 'sm']
                                        ]
                                    ]
                                ],
                                [
                                    'id' => 'col_f3',
                                    'elements' => [
                                        [
                                            'id' => 'el_f_social',
                                            'type' => 'social',
                                            'content' => ['style_preset' => 'soft', 'size' => 'md', 'color_preset' => 'brand']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $footerLayout = $settings->footer_layout_json ?: $defaultFooterLayout;

        $fakePage = [
            'id' => 'footer-global',
            'title' => 'Pie de Página Global (Footer)',
            'slug' => 'footer-global',
            'layout_json' => $footerLayout,
        ];

        return Inertia::render('Paginas/Builder', [
            'pagina'             => $fakePage,
            'categorias'         => CategoryResource::collection($categories)->resolve(),
            'articulosMuestra'   => ArticleResource::collection($articles)->resolve(),
            'descargasMuestra'   => \App\Services\PageBuilderResolverService::obtenerDescargasMuestraBuilder(),
            'paginasList'        => $paginasList,
            'rutaGuardarBuilder' => route('configuracion.footer.builder.update'),
            'rutaPreviewFrame'   => route('configuracion.footer.builder.preview-frame'),
            'esFooter'           => true,
        ]);
    }

    /**
     * Entrega el marco aislado de previsualización en tiempo real para el Footer.
     */
    public function previewFrameFooterBuilder(): Response
    {
        $settings = $this->service->obtener();
        $categories = Category::activas()
            ->select('id', 'name', 'slug', 'description', 'featured_image', 'icon', 'color', 'order', 'extra_fields_schema', 'category_builder_template_id', 'article_builder_template_id')
            ->orderBy('order', 'asc')
            ->get();

        $articles = Article::publicados()
            ->select('id', 'title', 'slug', 'excerpt', 'body', 'featured_image', 'icon', 'order', 'category_id', 'author_id', 'published_at', 'reading_time', 'extra_fields_data')
            ->with(['category:id,name,slug,extra_fields_schema', 'author:id,name'])
            ->orderByDesc('published_at')
            ->take(50)
            ->get();

        $defaultFooterLayout = [
            'version' => '1.0',
            'sections' => [
                [
                    'id' => 'sec_footer',
                    'name' => 'Sección Pie de Página',
                    'settings' => [
                        'container' => 'boxed',
                        'padding_top' => 'md',
                        'padding_bottom' => 'md',
                        'bg_preset' => 'zinc_900',
                        'text_color' => 'light',
                    ],
                    'rows' => [
                        [
                            'id' => 'row_footer',
                            'layout' => '1/3+1/3+1/3',
                            'columns' => [
                                [
                                    'id' => 'col_f1',
                                    'elements' => [
                                        [
                                            'id' => 'el_f_heading',
                                            'type' => 'heading',
                                            'content' => ['text' => $settings->site_name, 'tag' => 'h4', 'size' => 'h4']
                                        ],
                                        [
                                            'id' => 'el_f_text',
                                            'type' => 'text',
                                            'content' => ['html' => '<p>' . ($settings->site_description ?? '') . '</p>']
                                        ]
                                    ]
                                ],
                                [
                                    'id' => 'col_f2',
                                    'elements' => [
                                        [
                                            'id' => 'el_f_menu',
                                            'type' => 'menu',
                                            'content' => ['source' => 'location_header', 'orientation' => 'vertical', 'size' => 'sm']
                                        ]
                                    ]
                                ],
                                [
                                    'id' => 'col_f3',
                                    'elements' => [
                                        [
                                            'id' => 'el_f_social',
                                            'type' => 'social',
                                            'content' => ['style_preset' => 'soft', 'size' => 'md', 'color_preset' => 'brand']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $footerLayout = $settings->footer_layout_json ?: $defaultFooterLayout;

        $resolver = new PageBuilderResolverService();
        $resolvedLayout = $resolver->resolve($footerLayout);

        return Inertia::render('Paginas/BuilderPreviewFrame', [
            'pagina' => [
                'id'          => 'footer-global',
                'title'       => 'Pie de Página Global (Footer)',
                'slug'        => 'footer-global',
                'layout_json' => $resolvedLayout,
            ],
            'initialLayout'    => $resolvedLayout,
            'categorias'       => CategoryResource::collection($categories)->resolve(),
            'articulosMuestra' => ArticleResource::collection($articles)->resolve(),
            'esFooter'         => true,
        ]);
    }

    /**
     * Guardar el layout_json del Pie de Página.
     */
    public function updateFooterBuilder(Request $request)
    {
        $validated = $request->validate([
            'layout_json' => ['nullable', 'array'],
        ]);

        $layoutJson = $validated['layout_json'] ?? null;
        if (is_array($layoutJson)) {
            array_walk_recursive($layoutJson, function (&$value) {
                if (is_string($value)) {
                    $value = str_replace(
                        ['&nbsp;', "\xC2\xA0", "\xC2\xAD", '&shy;'],
                        [' ', ' ', '', ''],
                        $value
                    );
                }
            });
        }

        $this->service->actualizar([
            'footer_layout_json' => $layoutJson,
        ]);

        CacheInvalidationService::limpiarCachePublica();

        return back()->with('success', 'Pie de Página maquetado y guardado correctamente.');
    }
}
