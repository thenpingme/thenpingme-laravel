<?php

namespace Thenpingme\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Thenpingme\Laravel\Skeleton\SkeletonClass
 */
class Thenpingme extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'thenpingme';
    }
}
