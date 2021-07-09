<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\TaskPayload;
use Thenpingme\Tests\Fixtures\InvokableJob;

it('determines non unique tasks', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly();
        $schedule->command('test:command')->hourly();
    });

    tap(Thenpingme::scheduledTasks()->collisions(), function ($tasks) {
        $this->assertCount(2, $tasks);
        $this->assertNull($tasks[0]['extra']);
    });
});

it('determines non unique jobs', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->job(SomeJob::class)->hourly();
        $schedule->job(SomeJob::class)->hourly();
    });

    tap(Thenpingme::scheduledTasks()->collisions(), function ($tasks) {
        $this->assertCount(2, $tasks);
        $this->assertMatchesRegularExpression('/^Line [0-9]+ to [0-9]+ of/', $tasks[0]['extra']);
    });
});

it('determines closures are non unique tasks', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->call(function () {
            // This task does one thing
        })->everyMinute();

        $schedule->call(function () {
            // This task does another thing
        })->everyMinute();
    });

    tap(Thenpingme::scheduledTasks()->collisions(), function ($tasks) {
        $this->assertCount(2, $tasks);
        $this->assertMatchesRegularExpression('/^Line [0-9]+ to [0-9]+ of/', $tasks[0]['extra']);
    });
});

it('determines called jobs are non unique tasks', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->call(new InvokableJob)->everyMinute();
        $schedule->call(new InvokableJob)->everyMinute();
    });

    tap(Thenpingme::scheduledTasks()->collisions(), function ($tasks) {
        $this->assertCount(2, $tasks);
        $this->assertNull($tasks[0]['extra']);
    });
});

it('determines closures with unique descriptions are unique', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->call(function () {
            // This task does one thing
        })->everyMinute()->description('first task description');

        $schedule->call(function () {
            // This task does another thing
        })->everyMinute()->description('second task description');
    });

    expect(Thenpingme::scheduledTasks()->collisions())->toBeEmpty();
});

it('determines jobs with unique intervals are unique', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->job(SomeJob::class)->hourly();
        $schedule->job(SomeJob::class)->weekly();
    });

    expect(Thenpingme::scheduledTasks()->collisions())->toBeEmpty();
});

it('can fingerprint a closure task', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->call(function () {
            // some task
        })->everyMinute()->description('some task');
    });

    expect(TaskPayload::make(Arr::first(Thenpingme::scheduledTasks()))->toArray()['mutex'])
        ->toBe('thenpingme:'.sha1('* * * * *.some task'));
});

it('returns the current client version', function () {
    expect(Thenpingme::version())->not->toBeNull();
});
