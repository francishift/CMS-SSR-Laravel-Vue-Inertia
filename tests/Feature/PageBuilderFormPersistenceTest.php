<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Services\PageBuilderResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de integración para la resolución y SSR del elemento Formulario en el Page Builder.
 */
class PageBuilderFormPersistenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function resuelve_correctamente_esquema_publico_de_formulario_en_resolved_data(): void
    {
        $form = Form::create([
            'name'          => 'Formulario de Contacto',
            'slug'          => 'contacto-principal',
            'fields_schema' => [
                [
                    'id'          => 'fld_1',
                    'type'        => 'text',
                    'name'        => 'nombre',
                    'label'       => 'Nombre completo',
                    'placeholder' => 'Tu nombre',
                    'required'    => true,
                ],
                [
                    'id'          => 'fld_2',
                    'type'        => 'email',
                    'name'        => 'email',
                    'label'       => 'Correo electrónico',
                    'required'    => true,
                ],
            ],
            'settings'      => [
                'submit_button_text'  => 'Solicitar información',
                'submit_button_color' => 'indigo',
                'submit_button_align' => 'center',
            ],
            'is_active'     => true,
        ]);

        $layout = [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'   => 'sec_1',
                    'rows' => [
                        [
                            'id'      => 'row_1',
                            'columns' => [
                                [
                                    'id'       => 'col_1',
                                    'elements' => [
                                        [
                                            'id'      => 'el_form_1',
                                            'type'    => 'form',
                                            'content' => [
                                                'form_slug' => 'contacto-principal',
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

        $resolver = new PageBuilderResolverService();
        $resolvedLayout = $resolver->resolve($layout);

        $formElement = $resolvedLayout['sections'][0]['rows'][0]['columns'][0]['elements'][0];

        $this->assertArrayHasKey('resolved_data', $formElement);
        $this->assertEquals('contacto-principal', $formElement['resolved_data']['slug']);
        $this->assertEquals('Formulario de Contacto', $formElement['resolved_data']['name']);
        $this->assertCount(2, $formElement['resolved_data']['fields_schema']);
        $this->assertEquals('Solicitar información', $formElement['resolved_data']['settings']['submit_button_text']);
    }

    #[Test]
    public function resuelve_formulario_anidado_dentro_de_un_sublayout(): void
    {
        $form = Form::create([
            'name'          => 'Newsletter Rápido',
            'slug'          => 'newsletter-rapido',
            'fields_schema' => [
                [
                    'id'       => 'fld_email',
                    'type'     => 'email',
                    'name'     => 'email',
                    'label'    => 'Suscríbete',
                    'required' => true,
                ],
            ],
            'is_active'     => true,
        ]);

        $layout = [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'   => 'sec_1',
                    'rows' => [
                        [
                            'id'      => 'row_1',
                            'columns' => [
                                [
                                    'id'       => 'col_1',
                                    'elements' => [
                                        [
                                            'id'      => 'el_sub_1',
                                            'type'    => 'sublayout',
                                            'content' => [
                                                'layout' => '1/1',
                                                'rows'   => [
                                                    [
                                                        'id'      => 'subrow_1',
                                                        'columns' => [
                                                            [
                                                                'id'       => 'subcol_1',
                                                                'elements' => [
                                                                    [
                                                                        'id'      => 'el_nested_form',
                                                                        'type'    => 'form',
                                                                        'content' => [
                                                                            'form_slug' => 'newsletter-rapido',
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
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $resolver = new PageBuilderResolverService();
        $resolvedLayout = $resolver->resolve($layout);

        $nestedForm = $resolvedLayout['sections'][0]['rows'][0]['columns'][0]['elements'][0]['content']['rows'][0]['columns'][0]['elements'][0];

        $this->assertArrayHasKey('resolved_data', $nestedForm);
        $this->assertEquals('newsletter-rapido', $nestedForm['resolved_data']['slug']);
        $this->assertCount(1, $nestedForm['resolved_data']['fields_schema']);
    }

    #[Test]
    public function no_resuelve_formularios_inactivos(): void
    {
        Form::create([
            'name'          => 'Formulario Deshabilitado',
            'slug'          => 'form-inactivo',
            'fields_schema' => [
                ['id' => '1', 'type' => 'text', 'name' => 'campo', 'label' => 'Campo'],
            ],
            'is_active'     => false,
        ]);

        $layout = [
            'version'  => '1.0',
            'sections' => [
                [
                    'id'   => 'sec_1',
                    'rows' => [
                        [
                            'id'      => 'row_1',
                            'columns' => [
                                [
                                    'id'       => 'col_1',
                                    'elements' => [
                                        [
                                            'id'      => 'el_form_1',
                                            'type'    => 'form',
                                            'content' => [
                                                'form_slug' => 'form-inactivo',
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

        $resolver = new PageBuilderResolverService();
        $resolvedLayout = $resolver->resolve($layout);

        $formElement = $resolvedLayout['sections'][0]['rows'][0]['columns'][0]['elements'][0];
        $this->assertArrayNotHasKey('resolved_data', $formElement);
    }
}
