<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($app->environment() !== 'testing' || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('Refusing to run tests unless APP_ENV=testing and DB_CONNECTION=sqlite with DB_DATABASE=:memory:.');
        }

        return $app;
    }
}
