<?php

namespace Thenpingme\Client;

interface Client
{
    /**
     * @return Client
     */
    public function payload(array $payload);

    /**
     * @return Client
     */
    public static function ping();

    /**
     * @return Client
     */
    public static function setup();

    /**
     * @return Client
     */
    public static function sync();

    /**
     * @return Client
     */
    public function useSecret(?string $secret);

    public function baseUrl(): ?string;

    public function dispatch(): void;
}
