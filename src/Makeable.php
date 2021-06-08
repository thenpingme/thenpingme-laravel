<?php

declare(strict_types=1);

namespace Thenpingme;

trait Makeable
{
    /** @return self */
    public static function make()
    {
        return new self(...func_get_args());
    }
}
