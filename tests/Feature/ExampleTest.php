<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test básico de que la aplicación arranca correctamente.
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La página de inicio (pública) responde correctamente.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
