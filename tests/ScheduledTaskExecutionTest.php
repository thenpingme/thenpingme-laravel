<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Thenpingme\Tests\Fixtures\InvokableJob;
use Thenpingme\Tests\Fixtures\SomeJob;
use Thenpingme\ThenpingmePingJob;

class ScheduledTaskExecutionTest extends TestCase
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
    public function it_keeps_the_same_fingerprint_across_the_full_execution_of_a_command()
    {
        $this->app->make(Schedule::class)->command('thenpingme:testing');

        $this->artisan('schedule:run');

        tap(null, function ($fingerprint) {
            Queue::assertPushed(ThenpingmePingJob::class, function ($job) use (&$fingerprint) {
                if ($job->payload['type'] == 'ScheduledTaskStarting') {
                    $fingerprint = $job->payload['fingerprint'];
                }

                return $job->payload['type'] == 'ScheduledTaskStarting';
            });

            Queue::assertPushed(ThenpingmePingJob::class, function ($job) use ($fingerprint) {
                if ($job->payload['type'] == 'ScheduledTaskFinished') {
                    $this->assertEquals($fingerprint, $job->payload['fingerprint']);
                }

                return $job->payload['type'] == 'ScheduledTaskFinished';
            });
        });
    }

    /** @test */
    public function it_keeps_the_same_fingerprint_across_the_full_execution_of_a_job()
    {
        $this->app->make(Schedule::class)->job(SomeJob::class);

        $this->artisan('schedule:run');

        tap(null, function ($fingerprint) {
            Queue::assertPushed(ThenpingmePingJob::class, function ($job) use (&$fingerprint) {
                if ($job->payload['type'] == 'ScheduledTaskStarting') {
                    $fingerprint = $job->payload['fingerprint'];
                }

                return $job->payload['type'] == 'ScheduledTaskStarting';
            });

            Queue::assertPushed(ThenpingmePingJob::class, function ($job) use ($fingerprint) {
                if ($job->payload['type'] == 'ScheduledTaskFinished') {
                    $this->assertEquals($fingerprint, $job->payload['fingerprint']);
                }

                return $job->payload['type'] == 'ScheduledTaskFinished';
            });
        });
    }

    /** @test */
    public function it_keeps_the_same_fingerprint_across_the_full_execution_of_an_invokable_job()
    {
        $this->app->make(Schedule::class)->call(new InvokableJob);

        $this->artisan('schedule:run');

        tap(null, function ($fingerprint) {
            Queue::assertPushed(ThenpingmePingJob::class, function ($job) use (&$fingerprint) {
                if ($job->payload['type'] == 'ScheduledTaskStarting') {
                    $fingerprint = $job->payload['fingerprint'];
                }

                return $job->payload['type'] == 'ScheduledTaskStarting';
            });

            Queue::assertPushed(ThenpingmePingJob::class, function ($job) use ($fingerprint) {
                if ($job->payload['type'] == 'ScheduledTaskFinished') {
                    $this->assertEquals($fingerprint, $job->payload['fingerprint']);
                }

                return $job->payload['type'] == 'ScheduledTaskFinished';
            });
        });
    }

    /** @test */
    public function it_keeps_the_same_fingerprint_across_the_full_execution_of_a_shell_command()
    {
        $this->app->make(Schedule::class)->exec('echo "testing"');

        $this->artisan('schedule:run');

        tap(null, function ($fingerprint) {
            Queue::assertPushed(ThenpingmePingJob::class, function ($job) use (&$fingerprint) {
                if ($job->payload['type'] == 'ScheduledTaskStarting') {
                    $fingerprint = $job->payload['fingerprint'];
                }

                return $job->payload['type'] == 'ScheduledTaskStarting';
            });

            Queue::assertPushed(ThenpingmePingJob::class, function ($job) use ($fingerprint) {
                if ($job->payload['type'] == 'ScheduledTaskFinished') {
                    $this->assertEquals($fingerprint, $job->payload['fingerprint']);
                }

                return $job->payload['type'] == 'ScheduledTaskFinished';
            });
        });
    }

    /** @test */
    public function it_keeps_the_same_fingerprint_across_the_full_execution_of_a_closure()
    {
        $this->app->make(Schedule::class)->call(function () {
            echo 'testing';
        });

        $this->artisan('schedule:run');

        tap(null, function ($fingerprint) {
            Queue::assertPushed(ThenpingmePingJob::class, function ($job) use (&$fingerprint) {
                if ($job->payload['type'] == 'ScheduledTaskStarting') {
                    $fingerprint = $job->payload['fingerprint'];
                }

                return $job->payload['type'] == 'ScheduledTaskStarting';
            });

            Queue::assertPushed(ThenpingmePingJob::class, function ($job) use ($fingerprint) {
                if ($job->payload['type'] == 'ScheduledTaskFinished') {
                    $this->assertEquals($fingerprint, $job->payload['fingerprint']);
                }

                return $job->payload['type'] == 'ScheduledTaskFinished';
            });
        });
    }
}
