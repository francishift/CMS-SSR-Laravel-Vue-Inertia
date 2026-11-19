<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUpTraits()
    {
        // Se ejecuta ANTES de RefreshDatabase: impide tocar jamás una BD real por accidente
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException(
                'Seguridad de Tests: PHPUnit debe ejecutarse exclusivamente contra sqlite (:memory:). Ejecuta primero php artisan config:clear.'
            );
        }

        return parent::setUpTraits();
    }
}
