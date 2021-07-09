<?php

use Thenpingme\Signer\Signer;

it('calculates the signature for the given payload', function () {
    $signature = $this->app->make(Signer::class)->calculateSignature(['thenpingme' => 'test'], 'abc');

    $this->assertEquals(
        'd276b8572f3ea342d7946fc8c100266ceb0ffaee9443e95bde3762d66adb2146',
        $signature
    );
});
