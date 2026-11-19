<?php

namespace Tests\Feature;

use App\Http\Requests\UpdateSiteSettingRequest;
use App\Http\Resources\SiteSettingResource;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SiteSettingAnalyticsAndCookieConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_campos_de_analitica_y_cookie_consent_se_persisten_y_castean_correctamente(): void
    {
        $setting = SiteSetting::cached();
        $setting->update([
            'analytics_enabled'        => true,
            'analytics_provider'       => 'ga4',
            'analytics_measurement_id' => 'G-ABC1234567',
            'analytics_anonymize_ip'   => true,
            'cookie_consent_enabled'   => true,
            'cookie_consent_layout'    => 'box',
            'cookie_consent_position'  => 'bottom right',
            'cookie_consent_theme'     => 'auto',
            'cookie_consent_revision'  => 2,
            'cookie_consent_privacy_url' => '/politica-de-privacidad',
            'cookie_consent_cookie_url'  => '/politica-de-cookies',
            'cookie_consent_title'       => 'Configuración de privacidad',
            'cookie_consent_description' => 'Descripción detallada de cookies',
        ]);

        $fresh = $setting->fresh();
        $this->assertTrue($fresh->analytics_enabled);
        $this->assertEquals('ga4', $fresh->analytics_provider);
        $this->assertEquals('G-ABC1234567', $fresh->analytics_measurement_id);
        $this->assertTrue($fresh->analytics_anonymize_ip);
        $this->assertTrue($fresh->cookie_consent_enabled);
        $this->assertEquals('box', $fresh->cookie_consent_layout);
        $this->assertEquals('bottom right', $fresh->cookie_consent_position);
        $this->assertEquals(2, $fresh->cookie_consent_revision);
        $this->assertEquals('/politica-de-privacidad', $fresh->cookie_consent_privacy_url);
    }

    public function test_site_setting_resource_incluye_campos_de_analitica_y_cookies(): void
    {
        $setting = SiteSetting::cached();
        $setting->update([
            'analytics_enabled'        => true,
            'analytics_measurement_id' => 'G-XYZ999999',
            'cookie_consent_enabled'   => true,
            'cookie_consent_layout'    => 'bar',
            'cookie_consent_position'  => 'bottom',
        ]);

        $resource = (new SiteSettingResource($setting->fresh()))->resolve();
        $this->assertArrayHasKey('analytics_enabled', $resource);
        $this->assertTrue($resource['analytics_enabled']);
        $this->assertEquals('G-XYZ999999', $resource['analytics_measurement_id']);
        $this->assertTrue($resource['cookie_consent_enabled']);
        $this->assertEquals('bar', $resource['cookie_consent_layout']);
        $this->assertEquals('bottom', $resource['cookie_consent_position']);
    }

    public function test_update_site_setting_request_valida_formato_ga4_y_gtm(): void
    {
        $rules = (new UpdateSiteSettingRequest())->rules();

        // GA4 válido
        $validatorGa4 = Validator::make([
            'site_name'                => 'Mi Web',
            'primary_color'            => '#10b981',
            'heading_font'             => 'Inter',
            'body_font'                => 'Inter',
            'analytics_measurement_id' => 'G-12345ABCDE',
        ], $rules);
        $this->assertFalse($validatorGa4->fails());

        // GTM válido
        $validatorGtm = Validator::make([
            'site_name'                => 'Mi Web',
            'primary_color'            => '#10b981',
            'heading_font'             => 'Inter',
            'body_font'                => 'Inter',
            'analytics_measurement_id' => 'GTM-ABCDEFG',
        ], $rules);
        $this->assertFalse($validatorGtm->fails());

        // ID inválido (sin prefijo G- o GTM-)
        $validatorInvalido = Validator::make([
            'site_name'                => 'Mi Web',
            'primary_color'            => '#10b981',
            'heading_font'             => 'Inter',
            'body_font'                => 'Inter',
            'analytics_measurement_id' => 'UA-123456-1',
        ], $rules);
        $this->assertTrue($validatorInvalido->fails());
        $this->assertArrayHasKey('analytics_measurement_id', $validatorInvalido->errors()->toArray());
    }

    public function test_administrador_puede_actualizar_analitica_y_cookies_via_endpoint(): void
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->put(route('configuracion.update'), [
            'site_name'                => 'Mi Web Actualizada',
            'primary_color'            => '#10b981',
            'heading_font'             => 'Inter',
            'body_font'                => 'Inter',
            'analytics_enabled'        => true,
            'analytics_provider'       => 'ga4',
            'analytics_measurement_id' => 'G-TEST987654',
            'cookie_consent_enabled'   => true,
            'cookie_consent_layout'    => 'box',
            'cookie_consent_position'  => 'bottom right',
            'cookie_consent_theme'     => 'auto',
            'cookie_consent_revision'  => 1,
        ]);

        $response->assertRedirect();

        $fresh = SiteSetting::cached()->fresh();
        $this->assertTrue($fresh->analytics_enabled);
        $this->assertEquals('G-TEST987654', $fresh->analytics_measurement_id);
    }
}
