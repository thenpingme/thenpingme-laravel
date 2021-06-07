<?php

namespace Thenpingme\Exceptions;

use RuntimeException;

class CouldNotSendPing extends RuntimeException
{
    public static function missingBaseUrl(): CouldNotSendPing
    {
        return new CouldNotSendPing(
            app('translator')->get('thenpingme::translations.missing_base_url')
        );
    }

    public static function missingUrl(): CouldNotSendPing
    {
        return new CouldNotSendPing(
            app('translator')->get('thenpingme::translations.missing_endpoint_url')
        );
    }

    public static function missingSigningSecret(): CouldNotSendPing
    {
        return new CouldNotSendPing(
            app('translator')->get('thenpingme::translations.missing_signing_secret')
        );
    }
}
