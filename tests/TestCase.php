<?php

namespace Thenpingme\Tests;

use NunoMaduro\LaravelConsoleTask\LaravelConsoleTaskServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Thenpingme\ThenpingmeServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ThenpingmeServiceProvider::class,
            LaravelConsoleTaskServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('thenpingme.project_id', 'abc123');
        $app['config']->set('thenpingme.signing_key', 'def456');
    }
}
