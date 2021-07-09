<?php

use Thenpingme\Signer\Signer;

it('calculates the signature for the given payload', function () {
    expect($this->app->make(Signer::class)->calculateSignature(['thenpingme' => 'test'], 'abc'))
        ->toBe('d276b8572f3ea342d7946fc8c100266ceb0ffaee9443e95bde3762d66adb2146');
});
