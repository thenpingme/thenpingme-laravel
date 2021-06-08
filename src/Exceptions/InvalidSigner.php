<?php

declare(strict_types=1);

namespace Thenpingme\Exceptions;

use InvalidArgumentException;
use Thenpingme\Signer\Signer;

final class InvalidSigner extends InvalidArgumentException
{
    public static function doesntImplementSigner(string $signingClass): InvalidSigner
    {
        return new self(app('translator')->get('thenpingme::translations.invalid_signer', [
            'concrete' => $signingClass,
            'contract' => Signer::class,
        ]));
    }
}
