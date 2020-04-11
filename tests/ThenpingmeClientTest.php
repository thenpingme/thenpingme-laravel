<?php

namespace Thenpingme\Tests;

use Illuminate\Support\Facades\Config;
use Thenpingme\Client\Client;
use Thenpingme\Client\TestClient;
use Thenpingme\Exceptions\CouldNotSendPing;
use Thenpingme\Signer\ThenpingmeSigner;

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

        app(Client::class)->payload(['thenpingme' => 'test'])->ping()->dispatch();
    }

    public function it_sets_defaults_when_initialising_client()
    {
        $client = app(Client::class)->payload(['thenpingme' => 'test']);

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
            app(Client::class)->setup()->url
        );
    }

    /** @test */
    public function it_gets_a_ping_client()
    {
        $this->assertEquals(
            'http://thenpingme.test/api/projects/abc123/ping',
            app(Client::class)->ping()->url
        );
    }

    /** @test */
    public function it_sets_the_signature_header()
    {
        $client = app(Client::class)->useSecret('abc')->payload(['thenpingme' => 'test']);

        $this->assertEquals(
            ['Signature' => 'd276b8572f3ea342d7946fc8c100266ceb0ffaee9443e95bde3762d66adb2146'],
            $client->headers()
        );
    }
}
