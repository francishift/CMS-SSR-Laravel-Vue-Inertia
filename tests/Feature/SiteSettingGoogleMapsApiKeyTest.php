<?php

namespace Tests\Feature;

use App\Http\Requests\UpdateSiteSettingRequest;
use App\Http\Resources\SiteSettingResource;
use App\Models\Article;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PageBuilderResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SiteSettingGoogleMapsApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_maps_api_key_se_almacena_cifrada_en_la_base_de_datos(): void
    {
        $setting = SiteSetting::cached();
        $setting->update([
            'google_maps_api_key' => 'AIzaSyA_PruebaClaveSecreta1234567890',
        ]);

        // Verificar a través del modelo Eloquent (descifrado automático)
        $this->assertEquals('AIzaSyA_PruebaClaveSecreta1234567890', $setting->fresh()->google_maps_api_key);

        // Verificar en BD cruda que NO esté en texto plano
        $rawInDb = DB::table('site_settings')->where('id', $setting->id)->value('google_maps_api_key');
        $this->assertNotNull($rawInDb);
        $this->assertNotEquals('AIzaSyA_PruebaClaveSecreta1234567890', $rawInDb);
    }

    public function test_site_setting_resource_incluye_la_clave_de_google_maps(): void
    {
        $setting = SiteSetting::cached();
        $setting->update([
            'google_maps_api_key' => 'AIzaSyA_TestKey999',
        ]);

        $resource = (new SiteSettingResource($setting->fresh()))->resolve();
        $this->assertArrayHasKey('google_maps_api_key', $resource);
        $this->assertEquals('AIzaSyA_TestKey999', $resource['google_maps_api_key']);
    }

    public function test_update_site_setting_request_valida_correctamente_la_clave(): void
    {
        $rules = (new UpdateSiteSettingRequest())->rules();
        $this->assertArrayHasKey('google_maps_api_key', $rules);

        $validator = Validator::make([
            'site_name'           => 'Mi Sitio',
            'primary_color'       => '#10b981',
            'heading_font'        => 'Inter',
            'body_font'           => 'Inter',
            'google_maps_api_key' => 'AIzaSyA_TestValida',
        ], $rules);

        $this->assertTrue($validator->passes());
    }

    public function test_page_builder_resolver_service_resuelve_marcadores_dinamicos_de_articulos(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Sucursales',
            'slug' => 'sucursales',
            'is_active' => true,
        ]);

        $article = Article::create([
            'title' => 'Sucursal Centro',
            'slug' => 'sucursal-centro',
            'excerpt' => 'Nuestra tienda insignia en el centro',
            'body' => 'Contenido de la sucursal',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
            'extra_fields_data' => [
                'coordenadas' => '40.4168, -3.7038',
            ],
        ]);

        $layout = [
            'sections' => [
                [
                    'id' => 'sec_1',
                    'rows' => [
                        [
                            'id' => 'row_1',
                            'columns' => [
                                [
                                    'id' => 'col_1',
                                    'elements' => [
                                        [
                                            'id' => 'elem_map_1',
                                            'type' => 'map',
                                            'content' => [
                                                'mode' => 'dynamic',
                                                'dynamic_binding' => [
                                                    'source' => 'articles',
                                                    'category_id' => (string) $category->id,
                                                    'location_field' => 'coordenadas',
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

        $resolver = app(PageBuilderResolverService::class);
        $resolved = $resolver->resolve($layout);

        $elementResuelto = $resolved['sections'][0]['rows'][0]['columns'][0]['elements'][0];
        $this->assertArrayHasKey('resolved_data', $elementResuelto);
        $this->assertArrayHasKey('markers', $elementResuelto['resolved_data']);
        $this->assertCount(1, $elementResuelto['resolved_data']['markers']);

        $marcador = $elementResuelto['resolved_data']['markers'][0];
        $this->assertEquals('Sucursal Centro', $marcador['title']);
        $this->assertEquals(40.4168, $marcador['lat']);
        $this->assertEquals(-3.7038, $marcador['lng']);
    }

    public function test_page_builder_resolver_service_resuelve_mapa_dinamico_para_articulo_actual(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Hoteles',
            'slug' => 'hoteles',
            'is_active' => true,
        ]);

        $article = Article::create([
            'title' => 'Gran Hotel Madrid',
            'slug' => 'gran-hotel-madrid',
            'excerpt' => 'Hotel de 5 estrellas en Madrid',
            'body' => 'Texto detallado del hotel',
            'category_id' => $category->id,
            'author_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
            'extra_fields_data' => [
                'ubicacion' => 'Paseo de la Castellana 1, Madrid',
            ],
        ]);

        $layout = [
            'sections' => [
                [
                    'id' => 'sec_1',
                    'rows' => [
                        [
                            'id' => 'row_1',
                            'columns' => [
                                [
                                    'id' => 'col_1',
                                    'elements' => [
                                        [
                                            'id' => 'elem_map_single',
                                            'type' => 'map',
                                            'content' => [
                                                'mode' => 'dynamic',
                                                'dynamic_binding' => [
                                                    'source' => 'current_article',
                                                    'location_field' => 'ubicacion',
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

        $resolver = app(PageBuilderResolverService::class);
        $resolved = $resolver->resolveForArticle($layout, $article);

        $elementResuelto = $resolved['sections'][0]['rows'][0]['columns'][0]['elements'][0];
        $this->assertArrayHasKey('resolved_data', $elementResuelto);
        $this->assertArrayHasKey('markers', $elementResuelto['resolved_data']);
        $this->assertCount(1, $elementResuelto['resolved_data']['markers']);

        $marcador = $elementResuelto['resolved_data']['markers'][0];
        $this->assertEquals('Gran Hotel Madrid', $marcador['title']);
        $this->assertEquals('Paseo de la Castellana 1, Madrid', $marcador['address']);
    }
}
