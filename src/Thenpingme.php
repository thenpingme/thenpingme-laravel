<?php

namespace Thenpingme;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\WebhookServer\WebhookCall;

class Thenpingme
{
    const ENDPOINT_SETUP = 'setup';

    const ENDPOINT_PING = 'ping';

    public static function make()
    {
        return new static;
    }

    public function generateSigningKey()
    {
        return Str::random(512);
    }

    public function setup()
    {
        dd(config('webhook-server'));

        return WebhookCall::create()
            ->url($this->url(static::ENDPOINT_SETUP))
            ->useSecret(config('thenpingme.project_id'));
    }

    public function url($endpoint)
    {
        switch ($endpoint) {
            case static::ENDPOINT_SETUP:
                return str_replace(':project', config('thenpingme.project_id'), config('thenpingme.options.endpoints.setup'));
            case static::ENDPOINT_PING:
                return str_replace(':project', config('thenpingme.project_id'), config('thenpingme.options.endpoints.ping'));
            default:
                throw new InvalidArgumentException("Unknown client url [{$endpoint}]");
        }
    }
}
