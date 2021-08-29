<?php

namespace Thenpingme\Exceptions;

use Exception;
use Illuminate\Support\Facades\Config;

final class ThenpingmePingException extends Exception
{
    public function report()
    {
        logger()->error($this->message);
    }

    public static function couldNotPing($status, $body)
    {
        return new self(app('translator')->get('thenpingme::translations.could_not_ping', [
            'url' => parse_url(Config::get('thenpingme.api_url'), PHP_URL_HOST),
            'status' => $status,
            'body' => json_encode($body),
        ]));
    }
}
