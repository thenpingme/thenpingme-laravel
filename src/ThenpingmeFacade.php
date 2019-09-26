<?php

namespace Thenpingme\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Thenpingme\Laravel\Skeleton\SkeletonClass
 */
class ThenpingmeFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'thenpingme';
    }
}
