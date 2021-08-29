<?php

declare(strict_types=1);

namespace Thenpingme\Exceptions;

use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Thenpingme\Signer\Signer;

final class InvalidSigner extends InvalidArgumentException
{
    public static function doesntImplementSigner(string $signingClass): InvalidSigner
    {
        return new self(App::make('translator')->get('thenpingme::translations.invalid_signer', [
            'concrete' => $signingClass,
            'contract' => Signer::class,
        ]));
    }
}
