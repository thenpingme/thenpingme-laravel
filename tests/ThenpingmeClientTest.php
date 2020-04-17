<?php

namespace Thenpingme\Tests;

use Thenpingme\Client\Client;
use Thenpingme\Exceptions\CouldNotSendPing;

class ThenpingmeClientTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['thenpingme.api_url' => 'http://thenpingme.test/api']);
    }

    /** @test */
    public function it_does_not_send_a_ping_if_base_url_is_missing()
    {
        config(['thenpingme.api_url' => null]);

        $this->expectException(CouldNotSendPing::class);
        $this->expectExceptionMessageRegExp('/base URL is not set/');

        $this->app->make(Client::class)->payload(['thenpingme' => 'test'])->ping()->dispatch();
    }

    /** @test */
    public function it_does_not_send_a_ping_if_key_is_missing()
    {
        config(['thenpingme.signing_key' => null]);

        $this->expectException(CouldNotSendPing::class);
        $this->expectExceptionMessageRegExp('/signing secret is not set/');

        $this->app->make(Client::class)->payload(['thenpingme' => 'test'])->ping()->dispatch();
    }

    /** @test */
    public function it_does_not_send_a_ping_if_endpoint_is_missing()
    {
        $this->expectException(CouldNotSendPing::class);
        $this->expectExceptionMessageRegExp('/endpoint URL is not set/');

        $this->app->make(Client::class)->payload(['thenpingme' => 'test'])->dispatch();
    }

    /** @test */
    public function it_sets_defaults_when_initialising_client()
    {
        $client = $this->app->make(Client::class)->payload(['thenpingme' => 'test']);

        $this->assertEquals(
            '90b01e2e084d0df073d028a5c60a303618d5d56a194b08626f7236334f3345df',
            $client->headers()['Signature']
        );
    }

    /** @test */
    public function it_gets_a_setup_client()
    {
        $this->assertEquals(
            'http://thenpingme.test/api/projects/abc123/setup',
            $this->app->make(Client::class)->setup()->url
        );
    }

    /** @test */
    public function it_gets_a_ping_client()
    {
        $this->assertEquals(
            'http://thenpingme.test/api/projects/abc123/ping',
            $this->app->make(Client::class)->ping()->url
        );
    }

    /** @test */
    public function it_sets_the_signature_header()
    {
        $client = $this->app->make(Client::class)->useSecret('abc')->payload(['thenpingme' => 'test']);

        $this->assertEquals(
            ['Signature' => 'd276b8572f3ea342d7946fc8c100266ceb0ffaee9443e95bde3762d66adb2146'],
            $client->headers()
        );
    }
}
