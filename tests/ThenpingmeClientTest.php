<?php

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Bus;
use Thenpingme\Client\Client;
use Thenpingme\Exceptions\CouldNotSendPing;
use Thenpingme\ThenpingmePingJob;

beforeEach(function () {
    $this->translator = $this->app->make(Translator::class);

    config(['thenpingme.api_url' => 'http://thenpingme.test/api']);
});

it('does not send a ping if thenpingme is disabled', function () {
    Bus::fake();

    config(['thenpingme.enabled' => false]);

    $this->app->make(Client::class)->payload(['thenpingme' => 'test'])->ping()->dispatch();

    Bus::assertNotDispatched(ThenpingmePingJob::class);
});

it('does not send a ping if base url is missing', function () {
    config(['thenpingme.api_url' => null]);

    $this->expectException(CouldNotSendPing::class);
    $this->expectExceptionMessage($this->translator->get('thenpingme::translations.missing_base_url'));

    $this->app->make(Client::class)->payload(['thenpingme' => 'test'])->ping()->dispatch();
});

it('does not send a ping if key is missing', function () {
    config(['thenpingme.signing_key' => null]);

    $this->expectException(CouldNotSendPing::class);
    $this->expectExceptionMessage($this->translator->get('thenpingme::translations.missing_signing_secret'));

    $this->app->make(Client::class)->payload(['thenpingme' => 'test'])->ping()->dispatch();
});

it('does not send a ping if endpoint is missing', function () {
    $this->expectException(CouldNotSendPing::class);
    $this->expectExceptionMessage($this->translator->get('thenpingme::translations.missing_endpoint_url'));

    $this->app->make(Client::class)->payload(['thenpingme' => 'test'])->dispatch();
});

it('sets defaults when initialising client', function () {
    $client = $this->app->make(Client::class)->payload(['thenpingme' => 'test']);

    expect($this->app->make(Client::class)->payload(['thenpingme' => 'test']))
        ->headers()
        ->toHaveKey('Signature', '90b01e2e084d0df073d028a5c60a303618d5d56a194b08626f7236334f3345df');
});

it('gets a setup client', function () {
    expect($this->app->make(Client::class)->setup()->getUrl())
        ->toBe('http://thenpingme.test/api/projects/abc123/setup');
});

it('gets a ping client', function () {
    expect($this->app->make(Client::class)->ping()->getUrl())
        ->toBe('http://thenpingme.test/api/projects/abc123/ping');
});

it('gets a sync client', function () {
    expect($this->app->make(Client::class)->sync()->getUrl())
        ->toBe('http://thenpingme.test/api/projects/abc123/sync');
});

it('sets the signature header', function () {
    expect($this->app->make(Client::class)->useSecret('abc')->payload(['thenpingme' => 'test']))
        ->headers()
        ->toHaveKey('Signature', 'd276b8572f3ea342d7946fc8c100266ceb0ffaee9443e95bde3762d66adb2146');
});
