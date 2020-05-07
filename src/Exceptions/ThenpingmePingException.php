<?php

namespace Thenpingme\Exceptions;

use Exception;

class ThenpingmePingException extends Exception
{
    public function report()
    {
        logger()->error($this->message);
    }

    public static function couldNotPing($status, $body)
    {
        return new static(app('translator')->get('thenpingme::messages.could_not_ping', [
            'url' => parse_url(config('thenpingme.api_url'), PHP_URL_HOST),
            'status' => $status,
            'body' => $body,
        ]));
    }
}
