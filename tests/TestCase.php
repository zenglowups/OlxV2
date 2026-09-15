<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        // Fail before RefreshDatabase can migrate or reset anything.
        if ($app['config']->get('database.default') !== 'pgsql'
            || $app['config']->get('database.connections.pgsql.database') !== 'olxv2_testing') {
            throw new \RuntimeException('Tests may only use PostgreSQL database olxv2_testing.');
        }

        return $app;
    }
}
