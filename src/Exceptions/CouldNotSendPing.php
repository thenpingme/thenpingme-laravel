<?php

namespace Thenpingme\Exceptions;

use RuntimeException;

class CouldNotSendPing extends RuntimeException
{
    public static function missingBaseUrl()
    {
        return new static('Could not send ping because the >Then/Ping.me base URL is not set');
    }

    public static function missingUrl()
    {
        return new static('Could not send ping because the endpoint URL is not set');
    }

    public static function missingSigningSecret()
    {
        return new static('Could not send ping because the signing secret is not set');
    }
}
