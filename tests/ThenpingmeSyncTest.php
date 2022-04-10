<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\ThenpingmePingJob;

beforeEach(function () {
    $this->translator = $this->app->make(Translator::class);

    Config::set(['thenpingme.api_url' => 'http://thenpingme.test/api']);
});

it('fetches tasks to be synced', function () {
    Bus::fake();

    Config::set(['thenpingme.queue_ping' => true]);

    tap($this->app->make(Schedule::class), function ($schedule) {
        Thenpingme::shouldReceive('scheduledTasks')->andReturn(new ScheduledTaskCollection([
            $schedule->command('thenpingme:first')->description('This is the first task'),
            $schedule->command('thenpingme:second')->description('This is the second task'),
        ]));
        Thenpingme::shouldReceive('fingerprintTask')->times(4)->andReturn(
            Str::random(16),
            Str::random(16),
            Str::random(16),
            Str::random(16)
        );
        Thenpingme::shouldReceive('translateExpression');
        Thenpingme::shouldReceive('version')->once();
    });

    $this
        ->artisan('thenpingme:sync')
        ->expectsOutput($this->translator->get('thenpingme::translations.successful_sync'))
        ->assertExitCode(0);

    Bus::assertDispatched(ThenpingmePingJob::class);

    expect(Config::get('thenpingme.queue_ping'))->toBeFalse();
});

it('halts if duplicate tasks are encountered', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        Thenpingme::shouldReceive('scheduledTasks')->andReturn(new ScheduledTaskCollection([
            $schedule->command('thenpingme:first')->everyMinute()->description('This is the first task'),
            $schedule->command('thenpingme:first')->everyMinute()->description('This is the first task'),
        ]));
        Thenpingme::shouldReceive('fingerprintTask')->twice()->andReturn('the-fingerprint');
        Thenpingme::shouldReceive('translateExpression')->twice()->andReturn('Every minute');
    });

    $this
        ->artisan('thenpingme:sync')
        ->expectsOutput($this->translator->get('thenpingme::translations.indistinguishable_tasks'))
        ->assertExitCode(1);
});

it('handles tasks that are marked as skipped', function () {
    Bus::fake();

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('first:command')->hourly();
        $schedule->command('second:command')->everyMinute()->thenpingme(skip: true);
        $schedule->command('third:command')->everyMinute()->thenpingme(skip: false);
    });

    $this->artisan('thenpingme:sync')->assertExitCode(0);

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        expect($job->payload['tasks'])
            ->toHaveLength(2)
            ->toHaveKey('0.command', 'first:command')
            ->toHaveKey('1.command', 'third:command');

        return true;
    });
});
