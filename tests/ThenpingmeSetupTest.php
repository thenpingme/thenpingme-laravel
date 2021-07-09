<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\ThenpingmePingJob;

beforeEach(function () {
    Bus::fake();

    $this->translator = $this->app->make(Translator::class);

    config(['thenpingme.api_url' => 'http://thenpingme.test/api']);

    touch(base_path('.env.example'));
    touch(base_path('.env'));
});

afterEach(function () {
    unlink(base_path('.env.example'));
    unlink(base_path('.env'));
});

it('correctly sets environment variables', function () {
    Thenpingme::shouldReceive('generateSigningKey')->once()->andReturn('this-is-the-signing-secret');
    Thenpingme::shouldReceive('scheduledTasks')->andReturn(new ScheduledTaskCollection);
    Thenpingme::shouldReceive('version')->once();

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

    tap(loadEnv(base_path('.env')), function ($editor) {
        $this->assertEquals('aaa-bbbb-c1c1c1-ddd-ef1', $editor->getEnv('THENPINGME_PROJECT_ID'));
        $this->assertEquals('this-is-the-signing-secret', $editor->getEnv('THENPINGME_SIGNING_KEY'));
        $this->assertEquals('true', $editor->getEnv('THENPINGME_QUEUE_PING'));
    });

    tap(loadEnv(base_path('.env.example')), function ($editor) {
        $this->assertEquals('', $editor->getEnv('THENPINGME_PROJECT_ID'));
        $this->assertEquals('', $editor->getEnv('THENPINGME_SIGNING_KEY'));
        $this->assertEquals('true', $editor->getEnv('THENPINGME_QUEUE_PING'));
    });
});

it('sets up initial scheduled tasks', function () {
    config(['thenpingme.queue_ping' => true]);

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly();
    });

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        $this->assertEquals('aaa-bbbb-c1c1c1-ddd-ef1', $job->payload['project']['uuid']);
        $this->assertEquals(Config::get('thenpingme.signing_key'), $job->payload['project']['signing_key']);
        $this->assertEquals(Config::get('app.name'), $job->payload['project']['name']);

        $this->assertEquals('test:command', $job->payload['tasks'][0]['command']);
        $this->assertEquals('0 * * * *', $job->payload['tasks'][0]['expression']);

        return true;
    });

    $this->assertFalse(config('thenpingme.queue_ping'));
});

it('sets up initial scheduled tasks with explicit settings', function () {
    config(['thenpingme.queue_ping' => true]);

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly()->thenpingme([
            'grace_period' => 2,
            'allowed_run_time' => 2,
            'notify_after_consecutive_alerts' => 3,
        ]);
    });

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        $this->assertEquals(2, $job->payload['tasks'][0]['grace_period']);
        $this->assertEquals(2, $job->payload['tasks'][0]['allowed_run_time']);
        $this->assertEquals(3, $job->payload['tasks'][0]['notify_after_consecutive_alerts']);

        return true;
    });

    $this->assertFalse(config('thenpingme.queue_ping'));
});

it('sets up initial scheduled tasks with partial explicit settings', function () {
    config(['thenpingme.queue_ping' => true]);

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly()->thenpingme([
            'notify_after_consecutive_alerts' => 3,
        ]);
    });

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        $this->assertNull($job->payload['tasks'][0]['grace_period']);
        $this->assertNull($job->payload['tasks'][0]['allowed_run_time']);
        $this->assertEquals(3, $job->payload['tasks'][0]['notify_after_consecutive_alerts']);

        return true;
    });

    $this->assertFalse(config('thenpingme.queue_ping'));
});

it('handles missing environment file', function () {
    unlink(base_path('.env'));

    Thenpingme::shouldReceive('generateSigningKey')->never();
    Thenpingme::shouldReceive('scheduledTasks')->never();

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1')
        ->expectsOutput('THENPINGME_PROJECT_ID=aaa-bbbb-c1c1c1-ddd-ef1')
        ->assertExitCode(1);

    Bus::assertNotDispatched(ThenpingmePingJob::class);

    touch(base_path('.env'));
});

it('runs setup with tasks only', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly();
    });

    config(['thenpingme.project_id' => 'aaa-bbbb-c1c1c1-ddd-ef1']);

    $this->artisan('thenpingme:setup --tasks-only');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        $this->assertEquals('aaa-bbbb-c1c1c1-ddd-ef1', $job->payload['project']['uuid']);
        $this->assertEquals(Config::get('thenpingme.signing_key'), $job->payload['project']['signing_key']);
        $this->assertEquals(Config::get('app.name'), $job->payload['project']['name']);

        $this->assertEquals('test:command', $job->payload['tasks'][0]['command']);
        $this->assertEquals('0 * * * *', $job->payload['tasks'][0]['expression']);

        return true;
    });
});

it('runs setup with tasks only when env does not exist', function () {
    unlink(base_path('.env'));

    Thenpingme::shouldReceive('generateSigningKey')->once()->andReturn('secret');
    Thenpingme::shouldReceive('scheduledTasks')->andReturn(new ScheduledTaskCollection);
    Thenpingme::shouldReceive('version')->andReturn('1.2.3');

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly();
    });

    config(['thenpingme.project_id' => 'aaa-bbbb-c1c1c1-ddd-ef1']);
    config()->offsetUnset('thenpingme.signing_key');

    $this->artisan('thenpingme:setup --tasks-only')
        ->expectsOutput($this->translator->get('thenpingme::translations.signing_key_environment'))
        ->expectsOutput('THENPINGME_SIGNING_KEY=secret');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        $this->assertEquals('1.2.3', $job->payload['thenpingme']['version']);
        $this->assertEquals('aaa-bbbb-c1c1c1-ddd-ef1', $job->payload['project']['uuid']);
        $this->assertEquals('secret', $job->payload['project']['signing_key']);
        $this->assertEquals(Config::get('app.name'), $job->payload['project']['name']);

        return true;
    });

    touch(base_path('.env'));
});

it('exits if duplicate tasks are detected', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->job(SomeJob::class)->everyMinute();
        $schedule->job(SomeJob::class)->everyMinute();
    });

    $this->artisan('thenpingme:setup')->assertExitCode(1);

    Bus::assertNotDispatched(ThenpingmePingJob::class);
});
