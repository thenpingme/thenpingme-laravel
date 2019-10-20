<?php

namespace Thenpingme\Exceptions;

use InvalidArgumentException;
use Thenpingme\Signer\Signer;

class InvalidSigner extends InvalidArgumentException
{
    public static function doesntImplementSigner(string $signingClass)
    {
        $signingInterface = Signer::class;

        return new static("`{$signingClass}` does not implement `{$signingInterface}`");
    }
}
