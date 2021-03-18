<?php

namespace Thenpingme\Exceptions;

use InvalidArgumentException;
use Thenpingme\Signer\Signer;

class InvalidSigner extends InvalidArgumentException
{
    public static function doesntImplementSigner(string $signingClass)
    {
        return new static(app('translator')->get('thenpingme::translations.invalid_signer', [
            'concrete' => $signingClass,
            'contract' => Signer::class,
        ]));
    }
}
