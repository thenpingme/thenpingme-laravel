<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Thenpingme\Tests\Fixtures\InvokableJob;
use Thenpingme\Tests\Fixtures\SomeJob;
use Thenpingme\ThenpingmePingJob;

beforeEach(function () {
    Config::set([
        'thenpingme.project_id' => 'abc123',
        'thenpingme.signing_key' => 'super-secret',
    ]);

    Queue::fake();
});

it('keeps the same fingerprint across the full execution of a command', function () {
    $this->app->make(Schedule::class)->command('thenpingme:testing');

    $this->artisan('schedule:run');

    $fingerprint = null;

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) use (&$fingerprint) {
        if ($job->payload['type'] == 'ScheduledTaskStarting') {
            $fingerprint = $job->payload['fingerprint'];
        }

        return $job->payload['type'] == 'ScheduledTaskStarting';
    });

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) use ($fingerprint) {
        if ($job->payload['type'] == 'ScheduledTaskFinished') {
            expect($job->payload['fingerprint'])->toBe($fingerprint);
        }

        return $job->payload['type'] == 'ScheduledTaskFinished';
    });
});

it('keeps the same fingerprint across the full execution of a job', function () {
    $this->app->make(Schedule::class)->job(SomeJob::class);

    $this->artisan('schedule:run');

    $fingerprint = null;

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) use (&$fingerprint) {
        if ($job->payload['type'] == 'ScheduledTaskStarting') {
            $fingerprint = $job->payload['fingerprint'];
        }

        return $job->payload['type'] == 'ScheduledTaskStarting';
    });

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) use ($fingerprint) {
        if ($job->payload['type'] == 'ScheduledTaskFinished') {
            expect($job->payload['fingerprint'])->toBe($fingerprint);
        }

        return $job->payload['type'] == 'ScheduledTaskFinished';
    });
});

it('keeps the same fingerprint across the full execution of an invokable job', function () {
    $this->app->make(Schedule::class)->call(new InvokableJob);

    $this->artisan('schedule:run');

    $fingerprint = null;

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) use (&$fingerprint) {
        if ($job->payload['type'] == 'ScheduledTaskStarting') {
            $fingerprint = $job->payload['fingerprint'];
        }

        return $job->payload['type'] == 'ScheduledTaskStarting';
    });

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) use ($fingerprint) {
        if ($job->payload['type'] == 'ScheduledTaskFinished') {
            expect($job->payload['fingerprint'])->toBe($fingerprint);
        }

        return $job->payload['type'] == 'ScheduledTaskFinished';
    });
});

it('keeps the same fingerprint across the full execution of a shell command', function () {
    $this->app->make(Schedule::class)->exec('echo "testing"');

    $this->artisan('schedule:run');

    $fingerprint = null;

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) use (&$fingerprint) {
        if ($job->payload['type'] == 'ScheduledTaskStarting') {
            $fingerprint = $job->payload['fingerprint'];
        }

        return $job->payload['type'] == 'ScheduledTaskStarting';
    });

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) use ($fingerprint) {
        if ($job->payload['type'] == 'ScheduledTaskFinished') {
            expect($job->payload['fingerprint'])->toBe($fingerprint);
        }

        return $job->payload['type'] == 'ScheduledTaskFinished';
    });
});

it('keeps the same fingerprint across the full execution of a closure', function () {
    $this->app->make(Schedule::class)->call(function () {
        // we do nothing
    });

    $this->artisan('schedule:run');

    $fingerprint = null;

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) use (&$fingerprint) {
        if ($job->payload['type'] == 'ScheduledTaskStarting') {
            $fingerprint = $job->payload['fingerprint'];
        }

        return $job->payload['type'] == 'ScheduledTaskStarting';
    });

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) use ($fingerprint) {
        if ($job->payload['type'] == 'ScheduledTaskFinished') {
            expect($job->payload['fingerprint'])->toBe($fingerprint);
        }

        return $job->payload['type'] == 'ScheduledTaskFinished';
    });
});
