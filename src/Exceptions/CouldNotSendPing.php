<?php

declare(strict_types=1);

namespace Thenpingme\Exceptions;

use Illuminate\Support\Facades\App;
use RuntimeException;

final class CouldNotSendPing extends RuntimeException
{
    public static function missingBaseUrl(): CouldNotSendPing
    {
        return new self(
            App::make('translator')->get('thenpingme::translations.missing_base_url')
        );
    }

    public static function missingUrl(): CouldNotSendPing
    {
        return new self(
            App::make('translator')->get('thenpingme::translations.missing_endpoint_url')
        );
    }

    public static function missingSigningSecret(): CouldNotSendPing
    {
        return new self(
            App::make('translator')->get('thenpingme::translations.missing_signing_secret')
        );
    }
}
