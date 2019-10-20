<?php

namespace Thenpingme\Signer;

interface Signer
{
    public function calculateSignature(array $payload, string $signature): string;
}
