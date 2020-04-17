<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\TaskPayload;
use Thenpingme\Tests\Fixtures\SomeJob;

class ThenpingmeTest extends TestCase
{
    /** @test */
    public function it_determines_non_unique_tasks()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->command('test:command')->hourly();
        $schedule->command('test:command')->hourly();

        tap(Thenpingme::scheduledTasks()->collisions(), function ($tasks) {
            $this->assertCount(2, $tasks);
            $this->assertNull($tasks[0]['extra']);
        });
    }

    /** @test */
    public function it_determines_non_unique_jobs()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->job(SomeJob::class)->hourly();
        $schedule->job(SomeJob::class)->hourly();

        tap(Thenpingme::scheduledTasks()->collisions(), function ($tasks) {
            $this->assertCount(2, $tasks);
            $this->assertNull($tasks[0]['extra']);
        });
    }

    /** @test */
    public function it_determines_closures_are_non_unique_tasks()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->call(function () {
            // This task does one thing
        })->everyMinute();

        $schedule->call(function () {
            // This task does another thing
        })->everyMinute();

        tap(Thenpingme::scheduledTasks()->collisions(), function ($tasks) {
            $this->assertCount(2, $tasks);
            $this->assertRegExp('/^Line [0-9]+ to [0-9]+ of/', $tasks[0]['extra']);
        });
    }

    /** @test */
    public function it_determines_called_jobs_are_non_unique_tasks()
    {
        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->call(new InvokableJob)->everyMinute();
            $schedule->call(new InvokableJob)->everyMinute();
        });

        tap(Thenpingme::scheduledTasks()->collisions(), function ($tasks) {
            $this->assertCount(2, $tasks);
            $this->assertNull($tasks[0]['extra']);
        });
    }

        $schedule->call(function () {
            // This task does another thing
        })->everyMinute()->description('second task description');

        $this->assertEmpty(Thenpingme::scheduledTasks()->collisions());
    }

    /** @test */
    public function it_determines_jobs_with_unique_intervals_are_unique()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->job(SomeJob::class)->hourly();
        $schedule->job(SomeJob::class)->weekly();

        $this->assertEmpty(Thenpingme::scheduledTasks()->collisions());
    }

    /** @test */
    public function it_can_fingerprint_a_closure_task()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->call(function () {
            // some task
        })->everyMinute()->description('some task');

        $this->assertEquals(
            'thenpingme:'.sha1('* * * * *.some task'),
            TaskPayload::fromTask(Arr::first(Thenpingme::scheduledTasks()))->toArray()['mutex']
        );
    }
}
