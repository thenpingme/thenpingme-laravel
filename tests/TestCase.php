<?php

namespace Thenpingme\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Thenpingme\ThenpingmeServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ThenpingmeServiceProvider::class,
        ];
    }
}
