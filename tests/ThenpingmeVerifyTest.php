<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Scheduling\Schedule;
use Thenpingme\Tests\Fixtures\SomeJob;

class ThenpingmeVerifyTest extends TestCase
{
    /** @test */
    public function it_detects_non_unique_jobs()
    {
        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->job(SomeJob::class)->everyMinute();
            $schedule->job(SomeJob::class)->everyMinute();
        });

        $this->artisan('thenpingme:verify')
            ->expectsOutput('Tasks have been identified that are not uniquely distinguishable!')
            ->expectsOutput('Job-based tasks should set a description, or run on a unique schedule.')
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
            ->expectsOutput('Tasks have been identified that are not uniquely distinguishable!')
            ->expectsOutput('Closure-based tasks should set a description to ensure uniqueness.')
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
            ->expectsOutput('Your tasks are correctly configured and can be synced to thenping.me!')
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
