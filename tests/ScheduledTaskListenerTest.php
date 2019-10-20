<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Thenpingme\ThenpingmePingJob;

class ScheduledTaskListenerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set([
            'thenpingme.project_id' => 'abc123',
            'thenpingme.signing_key' => 'super-secret',
        ]);

        Queue::fake();
    }

    /** @test */
    public function it_listens_for_a_scheduled_task_starting()
    {
        $event = new Event(
            $this->mock(EventMutex::class),
            'artisan thenpingme:testing'
        );

        tap(app(Dispatcher::class), function ($dispatcher) use ($event) {
            $dispatcher->dispatch(new ScheduledTaskStarting($event));
        });

        Queue::assertPushed(ThenpingmePingJob::class, function ($job) {
            $this->assertEquals('https://thenping.me/api/projects/abc123/ping', $job->url);

            return true;
        });
    }

    /** @test */
    public function it_listens_for_a_scheduled_task_finishing()
    {
        $event = new Event(
            $this->mock(EventMutex::class),
            'artisan thenpingme:testing'
        );

        tap(app(Dispatcher::class), function ($dispatcher) use ($event) {
            $dispatcher->dispatch(new ScheduledTaskFinished($event, 1));
        });

        Queue::assertPushed(ThenpingmePingJob::class, function ($job) {
            $this->assertEquals('https://thenping.me/api/projects/abc123/ping', $job->url);

            return true;
        });
    }
}
