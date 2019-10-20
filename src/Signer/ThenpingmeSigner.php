<?php

namespace Thenpingme\Signer;

use Illuminate\Support\Facades\Config;

class ThenpingmeSigner implements Signer
{
    public function calculateSignature(array $payload, string $signature): string
    {
        $payload = json_encode($payload);

        return hash_hmac('sha256', $payload, $signature);
    }
}
