<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\ThenpingmePingJob;

class ThenpingmeSyncTest extends TestCase
{
    /** @var \Illuminate\Contracts\Translation\Translator */
    protected $translator;

    public function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->app->make(Translator::class);

        Queue::fake();

        config(['thenpingme.api_url' => 'http://thenpingme.test/api']);
    }

    /** @test */
    public function it_fetches_tasks_to_be_synced()
    {
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
        });

        $this->artisan('thenpingme:sync')
            ->expectsOutput($this->translator->get('thenpingme::messages.successful_sync'))
            ->assertExitCode(0);

        Queue::assertPushed(ThenpingmePingJob::class);
    }

    /** @test */
    public function it_halts_if_duplicate_tasks_are_encountered()
    {
        tap($this->app->make(Schedule::class), function ($schedule) {
            Thenpingme::shouldReceive('scheduledTasks')->andReturn(new ScheduledTaskCollection([
                $schedule->command('thenpingme:first')->everyMinute()->description('This is the first task'),
                $schedule->command('thenpingme:first')->everyMinute()->description('This is the first task'),
            ]));
            Thenpingme::shouldReceive('fingerprintTask')->twice()->andReturn('the-fingerprint');
            Thenpingme::shouldReceive('translateExpression')->twice()->andReturn('Every minute');
        });

        $this->artisan('thenpingme:sync')
            ->expectsOutput($this->translator->get('thenpingme::messages.indistinguishable_tasks'))
            ->assertExitCode(1);
    }
}
