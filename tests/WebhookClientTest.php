<?php

namespace Thenpingme\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Spatie\WebhookServer\WebhookCall;
use Thenpingme\Thenpingme;

class WebhookClientTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set(['thenpingme.project_id' => 'abc123']);
    }

    /**
     * @test
     * @dataProvider endpointsProvider
     */
    public function it_generates_the_correct_endpoint_url($url, $endpoint)
    {
        tap(new Thenpingme, function ($thenpingme) use ($url, $endpoint) {
            $this->assertEquals($url, $thenpingme->url($endpoint));
        });
    }

    /** @test */
    public function it_handles_unknown_url_type()
    {
        $this->expectException(InvalidArgumentException::class);

        Thenpingme::make()->url('something invalid');
    }

    /** @test */
    public function it_gets_a_setup_client()
    {
        $client = Thenpingme::make()->setup();

        $this->assertInstanceOf(WebhookCall::class, $client);
    }

    public function endpointsProvider()
    {
        return [
            ['https://thenping.me/api/projects/abc123/setup', Thenpingme::ENDPOINT_SETUP],
            ['https://thenping.me/api/projects/abc123/ping', Thenpingme::ENDPOINT_PING],
        ];
    }
}
