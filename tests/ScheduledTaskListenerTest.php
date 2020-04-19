<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
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
        $event = $this->app->make(Schedule::class)->command('thenpingme:testing');

        tap($this->app->make(Dispatcher::class), function ($dispatcher) use ($event) {
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
        $event = $this->app->make(Schedule::class)->command('thenpingme:testing');

        tap($this->app->make(Dispatcher::class), function ($dispatcher) use ($event) {
            $dispatcher->dispatch(new ScheduledTaskFinished($event, 1));
        });

        Queue::assertPushed(ThenpingmePingJob::class, function ($job) {
            $this->assertEquals('https://thenping.me/api/projects/abc123/ping', $job->url);

            return true;
        });
    }

    /** @test */
    public function it_listens_for_a_scheduled_task_skipped()
    {
        $event = $this->app->make(Schedule::class)->command('thenpingme:testing');

        tap($this->app->make(Dispatcher::class), function ($dispatcher) use ($event) {
            $dispatcher->dispatch(new ScheduledTaskSkipped($event, 1));
        });

        Queue::assertPushed(ThenpingmePingJob::class, function ($job) {
            $this->assertEquals('https://thenping.me/api/projects/abc123/ping', $job->url);

            return true;
        });
    }
}
