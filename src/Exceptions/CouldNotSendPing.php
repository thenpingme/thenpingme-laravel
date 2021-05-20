<?php

namespace Thenpingme\Exceptions;

use RuntimeException;

class CouldNotSendPing extends RuntimeException
{
    public static function missingBaseUrl()
    {
        return new static(app('translator')->get('thenpingme::translations.missing_base_url'));
    }

    public static function missingUrl()
    {
        return new static(app('translator')->get('thenpingme::translations.missing_endpoint_url'));
    }

    public static function missingSigningSecret()
    {
        return new static(app('translator')->get('thenpingme::translations.missing_signing_secret'));
    }
}
