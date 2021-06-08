<?php

declare(strict_types=1);

namespace Thenpingme\Signer;

interface Signer
{
    public function calculateSignature(array $payload, string $signature): string;
}
