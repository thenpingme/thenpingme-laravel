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

    Config::set([
        'thenpingme.project_name' => 'thenping.me test',
        'thenpingme.api_url' => 'http://thenpingme.test/api',
    ]);

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
        expect($editor)
            ->getEnv('THENPINGME_PROJECT_ID')->toBe('aaa-bbbb-c1c1c1-ddd-ef1')
            ->getEnv('THENPINGME_SIGNING_KEY')->toBe('this-is-the-signing-secret')
            ->getEnv('THENPINGME_QUEUE_PING')->toBe('true');
    });

    tap(loadEnv(base_path('.env.example')), function ($editor) {
        expect($editor)
            ->getEnv('THENPINGME_PROJECT_ID')->toBeEmpty()
            ->getEnv('THENPINGME_SIGNING_KEY')->toBeEmpty()
            ->getEnv('THENPINGME_QUEUE_PING')->toBe('true');
    });
});

it('sets up initial scheduled tasks', function () {
    Config::set(['thenpingme.queue_ping' => true]);

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly();
    });

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        expect($job)
            ->toHaveKey('payload.project.uuid', 'aaa-bbbb-c1c1c1-ddd-ef1')
            ->toHaveKey('payload.project.signing_key', Config::get('thenpingme.signing_key'))
            ->toHaveKey('payload.project.name', Config::get('thenpingme.project_name'))
            ->toHaveKey('payload.tasks.0.command', 'test:command')
            ->toHaveKey('payload.tasks.0.expression', '0 * * * *');

        return true;
    });

    $this->assertFalse(Config::get('thenpingme.queue_ping'));
});

it('sets up initial scheduled tasks with explicit settings', function () {
    Config::set(['thenpingme.queue_ping' => true]);

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly()->thenpingme(
            grace_period: 2,
            allowed_run_time: 2,
            notify_after_consecutive_alerts: 3,
        );
    });

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        expect($job->payload['tasks'][0])
            ->toHaveKey('grace_period', 2)
            ->toHaveKey('allowed_run_time', 2)
            ->toHaveKey('notify_after_consecutive_alerts', 3);

        return true;
    });

    expect(Config::get('thenpingme.queue_ping'))->toBeFalse();
});

it('sets up initial scheduled tasks with partial explicit settings', function () {
    Config::set(['thenpingme.queue_ping' => true]);

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly()->thenpingme(
            notify_after_consecutive_alerts: 3,
        );
    });

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        expect($job->payload['tasks'][0])
            ->toHaveKey('grace_period', 1)
            ->toHaveKey('allowed_run_time', 1)
            ->toHaveKey('notify_after_consecutive_alerts', 3);

        return true;
    });

    $this->assertFalse(Config::get('thenpingme.queue_ping'));
});

it('handles missing environment file', function () {
    unlink(base_path('.env'));

    Thenpingme::shouldReceive('generateSigningKey')->never();
    Thenpingme::shouldReceive('scheduledTasks')->never();

    $this
        ->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1')
        ->expectsOutput('THENPINGME_PROJECT_ID=aaa-bbbb-c1c1c1-ddd-ef1')
        ->assertExitCode(1);

    Bus::assertNotDispatched(ThenpingmePingJob::class);

    touch(base_path('.env'));
});

it('runs setup with tasks only', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly();
    });

    Config::set([
        'thenpingme.project_id' => 'aaa-bbbb-c1c1c1-ddd-ef1',
        'thenpingme.project_name' => 'Some other project name',
    ]);

    $this->artisan('thenpingme:setup --tasks-only');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        expect($job->payload)
            ->toHaveKey('project.uuid', 'aaa-bbbb-c1c1c1-ddd-ef1')
            ->toHaveKey('project.signing_key', Config::get('thenpingme.signing_key'))
            ->toHaveKey('project.name', 'Some other project name')
            ->toHaveKey('tasks.0.command', 'test:command')
            ->toHaveKey('tasks.0.expression', '0 * * * *');

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

    Config::set([
        'thenpingme.project_id' => 'aaa-bbbb-c1c1c1-ddd-ef1',
        'thenpingme.project_name' => 'thenping.me test',
    ]);

    Config::offsetUnset('thenpingme.signing_key');

    $this
        ->artisan('thenpingme:setup --tasks-only')
        ->expectsOutput($this->translator->get('thenpingme::translations.signing_key_environment'))
        ->expectsOutput('THENPINGME_SIGNING_KEY=secret');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        expect($job->payload)
            ->toHaveKey('thenpingme.version', '1.2.3')
            ->toHaveKey('project.uuid', 'aaa-bbbb-c1c1c1-ddd-ef1')
            ->toHaveKey('project.signing_key', 'secret')
            ->toHaveKey('project.name', 'thenping.me test');

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

it('allows overriding the project name', function () {
    Config::set(['thenpingme.project_name' => 'Not the app name']);

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly();
    });

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        expect($job->payload)->toHaveKey('project.name', 'Not the app name');

        return true;
    });
});

it('handles thenpingme being enabled or disabled', function () {
    config(['thenpingme.enabled' => false]);

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly();
    });

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1')
        ->expectsOutput($this->translator->get('thenpingme::translations.disabled'))
        ->assertExitCode(1);

    Bus::assertNotDispatched(ThenpingmePingJob::class);

    config(['thenpingme.enabled' => true]);

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1')
        ->assertExitCode(0);

    Bus::assertDispatched(ThenpingmePingJob::class);
});

it('handles tasks that are marked as skipped', function () {
    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('first:command')->hourly();
        $schedule->command('second:command')->everyMinute()->thenpingme(skip: true);
        $schedule->command('third:command')->everyMinute()->thenpingme(skip: false);
    });

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1')->assertExitCode(0);

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        expect($job->payload['tasks'])
            ->toHaveLength(2)
            ->toHaveKey('0.command', 'first:command')
            ->toHaveKey('1.command', 'third:command');

        return true;
    });
});
