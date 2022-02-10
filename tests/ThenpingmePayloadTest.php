<?php

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\ScheduledTaskFinishedPayload;
use Thenpingme\Payload\ScheduledTaskSkippedPayload;
use Thenpingme\Payload\ScheduledTaskStartingPayload;
use Thenpingme\Payload\SyncPayload;
use Thenpingme\Payload\TaskPayload;
use Thenpingme\Payload\ThenpingmePayload;
use Thenpingme\Payload\ThenpingmeSetupPayload;
use Thenpingme\TaskIdentifier;

beforeEach(function () {
    Config::set([
        'thenpingme.project_id' => 'abc123',
        'thenpingme.signing_key' => 'super-secret',
        'thenpingme.release' => 'this is the release',
    ]);

    putenv('APP_NAME=We changed the project name');
    putenv('SERVER_ADDR=10.1.1.1');
});

it('generates a task payload', function () {
    $task = $this->app->make(Schedule::class)->command('generate:payload')->description('This is the description');

    expect(TaskPayload::make($task)->toArray())
        ->toMatchSubset([
            'timezone' => '+00:00',
            'type' => TaskIdentifier::TYPE_COMMAND,
            'expression' => '* * * * *',
            'command' => 'generate:payload',
            'maintenance' => false,
            'without_overlapping' => false,
            'on_one_server' => false,
            'description' => 'This is the description',
            'mutex' => Thenpingme::fingerprintTask($task),
            'filtered' => false,
            'run_in_background' => false,
        ]);
});

it('determines if a task is filtered', function () {
    $task = $this->app->make(Schedule::class)
        ->command('thenpingme:filtered')
        ->description('This is the description')
        ->when(function () {
            return false;
        });

    expect(TaskPayload::make($task)->toArray())
        ->toMatchSubset([
            'timezone' => '+00:00',
            'type' => TaskIdentifier::TYPE_COMMAND,
            'expression' => '* * * * *',
            'command' => 'thenpingme:filtered',
            'maintenance' => false,
            'without_overlapping' => false,
            'on_one_server' => false,
            'description' => 'This is the description',
            'mutex' => Thenpingme::fingerprintTask($task),
            'filtered' => true,
            'run_in_background' => false,
        ]);
});

it('determines if a task is filtered by unlessBetween', function () {
    $task = $this->app->make(Schedule::class)
        ->command('thenpingme:filtered')
        ->hourly()
        ->description('This is the description')
        ->unlessBetween('00:00', '07:00');

    expect(TaskPayload::make($task)->toArray())
        ->toMatchSubset([
            'timezone' => '+00:00',
            'type' => TaskIdentifier::TYPE_COMMAND,
            'expression' => '0 * * * *',
            'command' => 'thenpingme:filtered',
            'maintenance' => false,
            'without_overlapping' => false,
            'on_one_server' => false,
            'description' => 'This is the description',
            'mutex' => Thenpingme::fingerprintTask($task),
            'filtered' => true,
            'run_in_background' => false,
        ]);
});

it('determines if a task is filtered by skip', function () {
    $task = $this->app->make(Schedule::class)
        ->command('thenpingme:filtered')
        ->hourly()
        ->description('This is the description')
        ->skip(function () {
            return true;
        });

    expect(TaskPayload::make($task)->toArray())
        ->toMatchSubset([
            'timezone' => '+00:00',
            'type' => TaskIdentifier::TYPE_COMMAND,
            'expression' => '0 * * * *',
            'command' => 'thenpingme:filtered',
            'maintenance' => false,
            'without_overlapping' => false,
            'on_one_server' => false,
            'description' => 'This is the description',
            'mutex' => Thenpingme::fingerprintTask($task),
            'filtered' => true,
            'run_in_background' => false,
        ]);
});

it('determines if a task is filtered by between', function () {
    $task = $this->app->make(Schedule::class)
        ->command('thenpingme:filtered')
        ->hourly()
        ->description('This is the description')
        ->between('07:00', '19:00');

    expect(TaskPayload::make($task)->toArray())
        ->toMatchSubset([
            'timezone' => '+00:00',
            'type' => TaskIdentifier::TYPE_COMMAND,
            'expression' => '0 * * * *',
            'command' => 'thenpingme:filtered',
            'maintenance' => false,
            'without_overlapping' => false,
            'on_one_server' => false,
            'description' => 'This is the description',
            'mutex' => Thenpingme::fingerprintTask($task),
            'filtered' => true,
            'run_in_background' => false,
        ]);
});

it('determines if a job runs in the background', function () {
    $task = $this->app->make(Schedule::class)
        ->command('thenpingme:background')
        ->description('This is the description')
        ->runInBackground();

    expect(TaskPayload::make($task)->toArray())
        ->toMatchSubset([
            'timezone' => '+00:00',
            'type' => TaskIdentifier::TYPE_COMMAND,
            'expression' => '* * * * *',
            'command' => 'thenpingme:background',
            'maintenance' => false,
            'without_overlapping' => false,
            'on_one_server' => false,
            'description' => 'This is the description',
            'mutex' => Thenpingme::fingerprintTask($task),
            'filtered' => false,
            'run_in_background' => true,
        ]);
});

it('generates a setup payload', function () {
    $scheduler = $this->app->make(Schedule::class);

    $events = ScheduledTaskCollection::make([
        $scheduler->command('thenpingme:first')->description('This is the first task'),
        $scheduler->command('thenpingme:second')->description('This is the second task'),
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
                    'type' => TaskIdentifier::TYPE_COMMAND,
                    'expression' => '* * * * *',
                    'command' => 'thenpingme:first',
                    'maintenance' => false,
                    'without_overlapping' => false,
                    'on_one_server' => false,
                    'run_in_background' => false,
                    'description' => 'This is the first task',
                    'mutex' => Thenpingme::fingerprintTask($events[0]),
                    'grace_period' => null,
                    'allowed_run_time' => null,
                    'notify_after_consecutive_alerts' => null,
                ],
                [
                    'type' => TaskIdentifier::TYPE_COMMAND,
                    'expression' => '* * * * *',
                    'command' => 'thenpingme:second',
                    'maintenance' => false,
                    'without_overlapping' => false,
                    'on_one_server' => false,
                    'run_in_background' => false,
                    'description' => 'This is the second task',
                    'mutex' => Thenpingme::fingerprintTask($events[1]),
                    'grace_period' => null,
                    'allowed_run_time' => null,
                    'notify_after_consecutive_alerts' => null,
                ],
            ],
        ]);
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

it('generates the correct payload for a scheduled task starting', function () {
    Carbon::setTestNow('2019-10-11 20:58:00', 'UTC');

    $event = new ScheduledTaskStarting(
        $this->app->make(Schedule::class)
            ->command('thenpingme:first')
            ->description('This is the first task')
            ->withoutOverlapping(10)
            ->onOneServer()
    );

    expect($payload = ThenpingmePayload::fromEvent($event))
        ->toBeInstanceOf(ScheduledTaskStartingPayload::class)
        ->toHaveKey('thenpingme.version', Thenpingme::version())
        ->toHaveKey('task.timezone', '+00:00')
        ->toHaveKey('fingerprint', $payload->fingerprint())
        ->toHaveKey('ip', '10.1.1.1')
        ->toHaveKey('hostname', gethostname())
        ->toHaveKey('type', 'ScheduledTaskStarting')
        ->toHaveKey('time', '2019-10-11T20:58:00+00:00')
        ->toHaveKey('expires', '2019-10-11T21:08:00+00:00')
        ->toHaveKey('environment', app()->environment())
        ->toHaveKey('task.without_overlapping', true)
        ->toHaveKey('task.on_one_server', true)
        ->toHaveKey('memory');
});

it('correctly identifies ip for a vapor app', function () {
    Carbon::setTestNow('2019-10-11 20:58:00', 'UTC');

    $event = new ScheduledTaskStarting(
        $this->app->make(Schedule::class)
            ->command('thenpingme:first')
            ->description('This is the first task')
            ->withoutOverlapping(10)
            ->onOneServer()
    );

    $_ENV['VAPOR_SSM_PATH'] = '/some/lambda/path';

    expect(ThenpingmePayload::fromEvent($event))
        ->toHaveKey('ip', ThenpingmePayload::getIp(gethostname()));

    unset($_ENV['VAPOR_SSM_PATH']);
});

it('includes the release if configured to do so', function () {
    Config::set(['thenpingme.release' => 'this is the release']);

    $event = new ScheduledTaskStarting(
        $this->app->make(Schedule::class)
            ->command('thenpingme:first')
            ->description('This is the first task')
            ->withoutOverlapping(10)
            ->onOneServer()
    );

    expect(ThenpingmePayload::fromEvent($event))
        ->toHaveKey('release', 'this is the release')
        ->toHaveKey('task.release', 'this is the release');
});

it('generates the correct payload for a scheduled task finished', function () {
    Carbon::setTestNow('2019-10-11 20:58:00', 'UTC');

    $event = new ScheduledTaskFinished(
        $this->app->make(Schedule::class)->command('thenpingme:first')->description('This is the first task'),
        1
    );

    expect($payload = ThenpingmePayload::fromEvent($event))
        ->toBeInstanceOf(ScheduledTaskFinishedPayload::class)
        ->toHaveKey('fingerprint', $payload->fingerprint())
        ->toHaveKey('ip', '10.1.1.1')
        ->toHaveKey('hostname', gethostname())
        ->toHaveKey('type', 'ScheduledTaskFinished')
        ->toHaveKey('time', '2019-10-11T20:58:00+00:00')
        ->toHaveKey('runtime', '1')
        ->toHaveKey('exit_code', null)
        ->toHaveKey('environment', app()->environment())
        ->toHaveKey('memory');
});

it('generates the correct payload for a scheduled task skipped', function () {
    Carbon::setTestNow('2019-10-11 20:58:00', 'UTC');

    $event = new ScheduledTaskSkipped(
        $this->app->make(Schedule::class)->command('thenpingme:first')->description('This is the first task'),
        1
    );

    expect($payload = ThenpingmePayload::fromEvent($event))
        ->toBeInstanceOf(ScheduledTaskSkippedPayload::class)
        ->toHaveKey('thenpingme.version', Thenpingme::version())
        ->toHaveKey('task.timezone', '+00:00')
        ->toHaveKey('fingerprint', $payload->fingerprint())
        ->toHaveKey('ip', '10.1.1.1')
        ->toHaveKey('hostname', gethostname())
        ->toHaveKey('type', 'ScheduledTaskSkipped')
        ->toHaveKey('time', '2019-10-11T20:58:00+00:00')
        ->toHaveKey('environment', app()->environment());
});

it('handles scheduled task specific timezones', function () {
    Carbon::setTestNow('2019-10-11 00:00:00', 'UTC');

    Config::set(['app.schedule_timezone' => '+10:30']);

    $event = new ScheduledTaskSkipped(
        $this
            ->app
            ->makeWith(Schedule::class, ['+10:30'])
            ->command('thenpingme:first')
            ->description('This is the first task'),
        1
    );

    expect($payload = ThenpingmePayload::fromEvent($event))
        ->toBeInstanceOf(ScheduledTaskSkippedPayload::class)
        ->toHaveKey('thenpingme.version', Thenpingme::version())
        ->toHaveKey('task.timezone', '+10:30')
        ->toHaveKey('fingerprint', $payload->fingerprint())
        ->toHaveKey('ip', '10.1.1.1')
        ->toHaveKey('hostname', gethostname())
        ->toHaveKey('type', 'ScheduledTaskSkipped')
        ->toHaveKey('time', '2019-10-11T00:00:00+00:00')
        ->toHaveKey('environment', app()->environment());
});

it('converts string timezones to utc offset', function () {
    Carbon::setTestNow('2019-10-11 00:00:00', 'UTC');

    Config::set(['app.schedule_timezone' => 'Australia/Adelaide']);

    $event = new ScheduledTaskSkipped(
        $this
            ->app
            ->makeWith(Schedule::class, ['Australia/Adelaide'])
            ->command('thenpingme:first')
            ->description('This is the first task'),
        1
    );

    expect($payload = ThenpingmePayload::fromEvent($event))
        ->toBeInstanceOf(ScheduledTaskSkippedPayload::class)
        ->toHaveKey('thenpingme.version', Thenpingme::version())
        ->toHaveKey('task.timezone', '+10:30')
        ->toHaveKey('fingerprint', $payload->fingerprint())
        ->toHaveKey('ip', '10.1.1.1')
        ->toHaveKey('hostname', gethostname())
        ->toHaveKey('type', 'ScheduledTaskSkipped')
        ->toHaveKey('time', '2019-10-11T00:00:00+00:00')
        ->toHaveKey('environment', app()->environment());
});

it('generates a sync payload', function () {
    Config::set(['thenpingme.project_name' => 'Some other project name']);

    $schedule = $this->app->make(Schedule::class);

    $events = ScheduledTaskCollection::make([
        $schedule->command('thenpingme:first')->description('This is the first synced task'),
    ]);

    expect(SyncPayload::make($events)->toArray())
        ->toMatchSubset([
            'thenpingme' => [
                'version' => Thenpingme::version(),
            ],
            'project' => [
                'uuid' => 'abc123',
                'name' => 'Some other project name',
                'release' => 'this is the release',
                'timezone' => '+00:00',
            ],
            'tasks' => [
                [
                    'type' => TaskIdentifier::TYPE_COMMAND,
                    'expression' => '* * * * *',
                    'command' => 'thenpingme:first',
                    'maintenance' => false,
                    'without_overlapping' => false,
                    'on_one_server' => false,
                    'description' => 'This is the first synced task',
                    'mutex' => Thenpingme::fingerprintTask($events[0]),
                ],
            ],
        ]);
});

it('identifies ip address', function () {
    $host = gethostname();

    // Vapor
    $_ENV['VAPOR_SSM_PATH'] = '/some/vapor/path';

    expect(ThenpingmePayload::getIp($host))->toBe(gethostbyname($host));

    unset($_ENV['VAPOR_SSM_PATH']);

    // SERVER_ADDR is set
    putenv('SERVER_ADDR=10.11.12.13');

    expect(ThenpingmePayload::getIp($host))->toBe('10.11.12.13');

    putenv('SERVER_ADDR');

    // Fallback
    if (PHP_OS == 'Linux') {
        // The only way to really test this works would be to duplicate the hostname
        // lookup that is executed in ThenpingmePayload, which is also pointless.
    } elseif (($ip = gethostbyname($host)) !== '127.0.0.1') {
        expect(ThenpingmePayload::getIp($host))->toBe($ip);
    } else {
        expect(ThenpingmePayload::getIp($host))->toBeNull();
    }
});

it('sets a file reference for closure tasks', function () {
    $task = $this->app->make(Schedule::class)->call(function () {
        echo 'anonymous';
    });

    // This is janky; don't put anything before here without updating
    // the start variable, otherwise the test assertion will fail.
    $start = __LINE__ - 6;
    $end = $start + 2;

    expect(TaskPayload::make($task))
        ->toHaveKey('command', static::class.":{$start} to {$end}")
        ->toHaveKey('extra.file', static::class)
        ->toHaveKey('extra.line', "{$start} to {$end}");
});
