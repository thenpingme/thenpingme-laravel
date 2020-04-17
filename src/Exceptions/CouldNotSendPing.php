<?php

namespace Thenpingme\Exceptions;

use RuntimeException;

class CouldNotSendPing extends RuntimeException
{
    public static function missingBaseUrl()
    {
        return new static(app('translator')->get('thenpingme::messages.missing_base_url'));
    }

    public static function missingUrl()
    {
        return new static(app('translator')->get('thenpingme::messages.missing_endpoint_url'));
    }

    public static function missingSigningSecret()
    {
        return new static(app('translator')->get('thenpingme::messages.missing_signing_secret'));
    }
}
