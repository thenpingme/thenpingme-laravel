<?php

namespace Thenpingme\Tests;

use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Spatie\WebhookServer\CallWebhookJob;
use Spatie\WebhookServer\WebhookCall;
use Thenpingme\Thenpingme;

class WebhookClientTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set('thenpingme.project_id', 'abc123');
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

    public function endpointsProvider()
    {
        return [
            'setup' => ['https://thenping.me/api/projects/abc123/setup', Thenpingme::ENDPOINT_SETUP],
            'ping' => ['https://thenping.me/api/projects/abc123/ping', Thenpingme::ENDPOINT_PING],
        ];
    }
}
