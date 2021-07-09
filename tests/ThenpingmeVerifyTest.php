<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Translation\Translator;
use Thenpingme\Tests\Fixtures\SomeJob;

beforeEach(fn () => $this->translator = $this->app->make(Translator::class));

it('detects non unique jobs', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->job(SomeJob::class)->everyMinute();
        $schedule->job(SomeJob::class)->everyMinute();
    });

    $this
        ->artisan('thenpingme:verify')
        ->expectsOutput($this->translator->get('thenpingme::translations.indistinguishable_tasks'))
        ->expectsOutput($this->translator->get('thenpingme::translations.duplicate_jobs'))
        ->assertExitCode(1);
});

it('detects non unique closures', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->call(function () {
            // Some job
        })->everyMinute();

        $schedule->call(function () {
            // Some other job
        })->everyMinute();
    });

    $this
        ->artisan('thenpingme:verify')
        ->expectsOutput($this->translator->get('thenpingme::translations.indistinguishable_tasks'))
        ->expectsOutput($this->translator->get('thenpingme::translations.duplicate_closures'))
        ->assertExitCode(1);
});

it('determines jobs with descriptions are unique', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->job(SomeJob::class)->everyMinute()->description('first job');
        $schedule->job(SomeJob::class)->everyMinute()->description('second job');
    });

    $this
        ->artisan('thenpingme:verify')
        ->expectsOutput($this->translator->get('thenpingme::translations.healthy_tasks'))
        ->assertExitCode(0);
});

it('determines closures with descriptions are unique', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->call(function () {
            // Some job
        })->everyMinute()->description('first closure');

        $schedule->call(function () {
            // Some other job
        })->everyMinute()->description('second closure');
    });

    $this
        ->artisan('thenpingme:verify')
        ->expectsOutput('Your tasks are correctly configured and can be synced to thenping.me!')
        ->assertExitCode(0);
});

it('determines all tasks are ok', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->job(SomeJob::class)->everyMinute();
        $schedule->call(function () {
            // Some job
        })->everyMinute();
    });

    $this
        ->artisan('thenpingme:verify')
        ->expectsOutput('Your tasks are correctly configured and can be synced to thenping.me!')
        ->assertExitCode(0);
});
