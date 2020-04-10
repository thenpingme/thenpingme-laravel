<?php

namespace Thenpingme\Exceptions;

use Exception;

class ThenpingmePingException extends Exception
{
    public function report()
    {
        logger()->error($this->message);
    }

    public static function couldNotPing($status)
    {
        return new static(vsprintf('Could not send ping to %s: [%s]', [
            parse_url(config('thenpingme.api_url'), PHP_URL_HOST),
            $status,
        ]));
    }
}
