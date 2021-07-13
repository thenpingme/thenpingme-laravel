<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\TaskPayload;
use Thenpingme\Payload\ThenpingmeSetupPayload;
use Thenpingme\ThenpingmePingJob;

beforeEach(function () {
    Bus::fake();

    Config::set([
        'app.name' => 'We changed the project name',
        'thenpingme.project_id' => 'abc123',
        'thenpingme.signing_key' => 'super-secret',
        'thenpingme.release' => 'this is the release',
    ]);

    putenv('SERVER_ADDR=10.1.1.1');

    touch(base_path('.env.example'));
    touch(base_path('.env'));
});

afterEach(function () {
    unlink(base_path('.env.example'));
    unlink(base_path('.env'));
});

it('sets up initial scheduled tasks with explicit settings', function () {
    config(['thenpingme.queue_ping' => true]);

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

    expect(config('thenpingme.queue_ping'))->toBeFalse();
});

it('sets up initial scheduled tasks with partial explicit settings', function () {
    config(['thenpingme.queue_ping' => true]);

    tap($this->app->make(Schedule::class), function ($schedule) {
        $schedule->command('test:command')->hourly()->thenpingme(
            notify_after_consecutive_alerts: 3,
        );
    });

    $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

    Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
        expect($job->payload['tasks'][0])
            ->toHaveKey('grace_period', null)
            ->toHaveKey('allowed_run_time', null)
            ->toHaveKey('notify_after_consecutive_alerts', 3);

        return true;
    });

    $this->assertFalse(config('thenpingme.queue_ping'));
});

it('generates a setup payload with explicit settings', function () {
    $scheduler = $this->app->make(Schedule::class);

    $events = ScheduledTaskCollection::make([
        $scheduler
            ->command('thenpingme:first')
            ->description('This is the first task')
            ->thenpingme(
                grace_period: 2,
                allowed_run_time: 2,
                notify_after_consecutive_alerts: 3,
            ),
    ]);

    expect(ThenpingmeSetupPayload::make($events, 'super-secret')->toArray())
        ->toMatchSubset([
            'thenpingme' => [
                'version' => Thenpingme::version(),
            ],
            'project' => [
                'uuid' => 'abc123',
                'name' => 'We changed the project name',
                'signing_key' => 'super-secret',
                'timezone' => '+00:00',
            ],
            'tasks' => [
                [
                    'grace_period' => 2,
                    'allowed_run_time' => 2,
                    'notify_after_consecutive_alerts' => 3,
                ],
            ],
        ]);
});

it('generates a setup payload with partial explicit settings', function () {
    $scheduler = $this->app->make(Schedule::class);

    $events = ScheduledTaskCollection::make([
        $scheduler
            ->command('thenpingme:first')
            ->description('This is the first task')
            ->thenpingme(notify_after_consecutive_alerts: 3),
    ]);

    expect(ThenpingmeSetupPayload::make($events, 'super-secret')->toArray())
        ->toMatchSubset([
            'thenpingme' => [
                'version' => Thenpingme::version(),
            ],
            'project' => [
                'uuid' => 'abc123',
                'name' => 'We changed the project name',
                'signing_key' => 'super-secret',
                'timezone' => '+00:00',
            ],
            'tasks' => [
                [
                    'grace_period' => null,
                    'allowed_run_time' => null,
                    'notify_after_consecutive_alerts' => 3,
                ],
            ],
        ]);
});

it('can specify a set of setting defaults', function () {
    Thenpingme::defaults(
        grace_period: 5,
        allowed_run_time: 8,
        notify_after_consecutive_alerts: 3,
    );

    $task = $this->app->make(Schedule::class)->command('thenpingme:defaults')->description('Using defaults');

    expect(TaskPayload::make($task)->toArray())
        ->toMatchSubset([
            'command' => 'thenpingme:defaults',
            'description' => 'Using defaults',
            'grace_period' => 5,
            'allowed_run_time' => 8,
            'notify_after_consecutive_alerts' => 3,
        ]);
});

it('can override default settings', function () {
    Thenpingme::defaults(
        grace_period: 5,
        allowed_run_time: 8,
        notify_after_consecutive_alerts: 3,
    );

    $task = $this->app->make(Schedule::class)->command('thenpingme:defaults')->description('Using defaults')->thenpingme(
        grace_period: 2
    );

    expect(TaskPayload::make($task)->toArray())
        ->toMatchSubset([
            'command' => 'thenpingme:defaults',
            'description' => 'Using defaults',
            'grace_period' => 2,
            'allowed_run_time' => 8,
            'notify_after_consecutive_alerts' => 3,
        ]);

    $task = $this->app->make(Schedule::class)->command('thenpingme:defaults')->description('Using defaults')->thenpingme(
        allowed_run_time: 5
    );

    expect(TaskPayload::make($task)->toArray())
        ->toMatchSubset([
            'command' => 'thenpingme:defaults',
            'description' => 'Using defaults',
            'grace_period' => 5,
            'allowed_run_time' => 5,
            'notify_after_consecutive_alerts' => 3,
        ]);

    $task = $this->app->make(Schedule::class)->command('thenpingme:defaults')->description('Using defaults')->thenpingme(
        notify_after_consecutive_alerts: 2
    );

    expect(TaskPayload::make($task)->toArray())
        ->toMatchSubset([
            'command' => 'thenpingme:defaults',
            'description' => 'Using defaults',
            'grace_period' => 5,
            'allowed_run_time' => 8,
            'notify_after_consecutive_alerts' => 2,
        ]);
});
