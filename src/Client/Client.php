<?php

namespace Thenpingme\Client;

interface Client
{
    public function baseUrl(): string;

    public function payload(array $payload): self;

    public static function ping(): self;

    public static function setup(): self;
}
