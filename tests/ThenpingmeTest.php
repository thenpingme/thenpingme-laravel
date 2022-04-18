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

    expect(Thenpingme::scheduledTasks()->collisions())
        ->count()->toBe(2)
        ->first()->extra->toBeNull();
});

it('determines non unique jobs', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->job(SomeJob::class)->hourly();
        $schedule->job(SomeJob::class)->hourly();
    });

    expect(Thenpingme::scheduledTasks()->collisions())
        ->count()->toBe(2)
        ->first()->extra->toMatch('/^Line [0-9]+ to [0-9]+ of/');
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

    expect(Thenpingme::scheduledTasks()->collisions())
        ->count()->toBe(2)
        ->first()->extra->toMatch('/^Line [0-9]+ to [0-9]+ of/');
});

it('determines called jobs are non unique tasks', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->call(new InvokableJob)->everyMinute();
        $schedule->call(new InvokableJob)->everyMinute();
    });

    expect(Thenpingme::scheduledTasks()->collisions())
        ->count()->toBe(2)
        ->first()->extra->toBeNull();
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

    expect(TaskPayload::make(Arr::first(Thenpingme::scheduledTasks())))
        ->toArray()
        ->toHaveKey('mutex', 'thenpingme:'.sha1('* * * * *.some task'));
});

it('returns the current client version', function () {
    expect(Thenpingme::version())->not->toBeNull();
});

it('handles an invalid cron expression', function () {
    expect(Thenpingme::translateExpression('not a cron expression'))
        ->toBe('not a cron expression');
});
