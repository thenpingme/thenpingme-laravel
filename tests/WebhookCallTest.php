<?php

namespace Thenpingme\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Spatie\WebhookServer\CallWebhookJob;
use Spatie\WebhookServer\WebhookCall;
use Thenpingme\Thenpingme;

class WebhookCallTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        Config::set([
            'thenpingme.project_id' => 'abc123',
            'thenpingme.signing_key' => 'this-is-a-secret',
            'thenpingme.tags' => ['arbitrary', 'thenpingme'],
        ]);
    }

    /** @test */
    public function it_gets_a_setup_client()
    {
        Thenpingme::make()->setup()->dispatch();

        Queue::assertPushed(CallWebhookJob::class, function (CallWebhookJob $job) {
            $this->assertEquals('https://thenping.me/api/projects/abc123/setup', $job->webhookUrl);
            $this->assertContains('thenpingme', $job->tags);
            $this->assertContains('arbitrary', $job->tags);
            $this->assertCount(2, $job->tags);

            return true;
        });
    }

    /** @test */
    public function it_gets_a_ping_client()
    {
        Thenpingme::make()->ping()->dispatch();

        Queue::assertPushed(CallWebhookJob::class, function (CallWebhookJob $job) {
            $this->assertEquals('https://thenping.me/api/projects/abc123/ping', $job->webhookUrl);
            $this->assertContains('thenpingme', $job->tags);
            $this->assertContains('arbitrary', $job->tags);
            $this->assertCount(2, $job->tags);

            return true;
        });
    }
}
