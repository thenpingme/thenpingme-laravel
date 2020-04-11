<?php

namespace Thenpingme\Signer;

use Illuminate\Support\Facades\Config;

class ThenpingmeSigner implements Signer
{
    public function calculateSignature(array $payload, string $signature): string
    {
        return hash_hmac('sha256', json_encode($payload), $signature);
    }
}
