<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\ThenpingmePingJob;

beforeEach(function () {
    $this->translator = $this->app->make(Translator::class);

    config(['thenpingme.api_url' => 'http://thenpingme.test/api']);
});

it('fetches tasks to be synced', function () {
    Bus::fake();

    config(['thenpingme.queue_ping' => true]);

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

    expect(config('thenpingme.queue_ping'))->toBeFalse();
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
