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
        return new static(sprintf('Could not send ping to thenping.me: [%s]', $status));
    }
}
