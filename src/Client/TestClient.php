<?php

namespace Thenpingme\Client;

class TestClient extends ThenpingmeClient implements Client
{
    public function baseUrl(): string
    {
        return 'http://thenpingme.test/api';
    }
}
