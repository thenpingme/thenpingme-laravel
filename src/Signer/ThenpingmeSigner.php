<?php

declare(strict_types=1);

namespace Thenpingme\Signer;

class ThenpingmeSigner implements Signer
{
    public function calculateSignature(array $payload, string $signature): string
    {
        return hash_hmac('sha256', json_encode($payload), $signature);
    }
}
