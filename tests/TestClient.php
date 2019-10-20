<?php

namespace Thenpingme\Tests;

use Thenpingme\Client\Client;
use Thenpingme\Client\ThenpingmeClient;

class TestClient extends ThenpingmeClient implements Client
{
    public function baseUrl(): string
    {
        return 'http://thenpingme.test/api';
    }
}
