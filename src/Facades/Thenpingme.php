<?php

declare(strict_types=1);

namespace Thenpingme\Facades;

use Illuminate\Support\Facades\Facade;

class Thenpingme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'thenpingme';
    }
}
