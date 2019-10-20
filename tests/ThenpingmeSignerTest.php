<?php

namespace Thenpingme\Tests;

use Illuminate\Support\Facades\Config;
use Thenpingme\Signer\Signer;
use Thenpingme\Signer\ThenpingmeSigner;

class ThenpingmeSignerTest extends TestCase
{
    /** @test */
    public function it_calculates_the_signature_for_the_given_payload()
    {
        $signature = app(Signer::class)->calculateSignature(['thenpingme' => 'test'], 'abc');

        $this->assertEquals(
            'd276b8572f3ea342d7946fc8c100266ceb0ffaee9443e95bde3762d66adb2146',
            $signature
        );
    }
}
