<?php

namespace Thenpingme\Laravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Thenpingme\Laravel\ThenpingmeServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ThenpingmeServiceProvider::class,
        ];
    }
}
