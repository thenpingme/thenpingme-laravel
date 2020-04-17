<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Translation\Translator;
use Thenpingme\Tests\Fixtures\SomeJob;

class ThenpingmeVerifyTest extends TestCase
{
    /** @var \Illuminate\Contracts\Translation\Translator */
    protected $translator;

    public function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->app->make(Translator::class);
    }

    /** @test */
    public function it_detects_non_unique_jobs()
    {
        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->job(SomeJob::class)->everyMinute();
            $schedule->job(SomeJob::class)->everyMinute();
        });

        $this->artisan('thenpingme:verify')
            ->expectsOutput($this->translator->get('thenpingme::messages.indistinguishable_tasks'))
            ->expectsOutput($this->translator->get('thenpingme::messages.duplicate_jobs'))
            ->assertExitCode(1);
    }

    /** @test */
    public function it_detects_non_unique_closures()
    {
        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->call(function () {
                // Some job
            })->everyMinute();

            $schedule->call(function () {
                // Some other job
            })->everyMinute();
        });

        $this->artisan('thenpingme:verify')
            ->expectsOutput($this->translator->get('thenpingme::messages.indistinguishable_tasks'))
            ->expectsOutput($this->translator->get('thenpingme::messages.duplicate_closures'))
            ->assertExitCode(1);
    }

    /** @test */
    public function it_determines_jobs_with_descriptions_are_unique()
    {
        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->job(SomeJob::class)->everyMinute()->description('first job');
            $schedule->job(SomeJob::class)->everyMinute()->description('second job');
        });

        $this->artisan('thenpingme:verify')
            ->expectsOutput($this->translator->get('thenpingme::messages.healthy_tasks'))
            ->assertExitCode(0);
    }

    /** @test */
    public function it_determines_closures_with_descriptions_are_unique()
    {
        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->call(function () {
                // Some job
            })->everyMinute()->description('first closure');

            $schedule->call(function () {
                // Some other job
            })->everyMinute()->description('second closure');
        });

        $this->artisan('thenpingme:verify')
            ->expectsOutput('Your tasks are correctly configured and can be synced to thenping.me!')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_determines_all_tasks_are_ok()
    {
        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->job(SomeJob::class)->everyMinute();
            $schedule->call(function () {
                // Some job
            })->everyMinute();
        });

        $this->artisan('thenpingme:verify')
            ->expectsOutput('Your tasks are correctly configured and can be synced to thenping.me!')
            ->assertExitCode(0);
    }
}
