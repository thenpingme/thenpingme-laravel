<?php

namespace Thenpingme;

use Illuminate\Support\Facades\Config;
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
        return $this->baseWebhookCall()
            ->url($this->url(static::ENDPOINT_SETUP))
            ->useSecret(config('thenpingme.project_id'));
    }

    public function ping()
    {
        return $this->baseWebhookCall()
            ->url($this->url(static::ENDPOINT_PING))
            ->useSecret(config('thenpingme.signing_key'));
    }

    public function url($endpoint)
    {
        $config = Config::get('thenpingme');

        switch ($endpoint) {
            case static::ENDPOINT_SETUP:
                return str_replace(':project', $config['project_id'], $config['options']['endpoints']['setup']);
            case static::ENDPOINT_PING:
                return str_replace(':project', $config['project_id'], $config['options']['endpoints']['ping']);
            default:
                throw new InvalidArgumentException("Unknown client url [{$endpoint}]");
        }
    }

    protected function baseWebhookCall()
    {
        return WebhookCall::create()
            ->withTags(array_unique(array_merge(['thenpingme'], config('thenpingme.tags', []))));
    }
}
