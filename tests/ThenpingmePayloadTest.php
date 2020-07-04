<?php

namespace Thenpingme\Tests;

use Exception;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\Assert;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\ScheduledTaskFailedPayload;
use Thenpingme\Payload\ScheduledTaskFinishedPayload;
use Thenpingme\Payload\ScheduledTaskSkippedPayload;
use Thenpingme\Payload\ScheduledTaskStartingPayload;
use Thenpingme\Payload\SyncPayload;
use Thenpingme\Payload\ThenpingmePayload;
use Thenpingme\Payload\ThenpingmeSetupPayload;
use Thenpingme\TaskIdentifier;

class ThenpingmePayloadTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set([
            'app.name' => 'We changed the project name',
            'thenpingme.project_id' => 'abc123',
            'thenpingme.signing_key' => 'super-secret',
            'thenpingme.release' => 'this is the release',
        ]);

        putenv('SERVER_ADDR=10.1.1.1');
    }

    /** @test */
    public function it_generates_a_task_payload()
    {
        $task = $this->app->make(Schedule::class)->command('generate:payload')->description('This is the description');

        tap(ThenpingmePayload::fromTask($task)->toArray(), function ($payload) use ($task) {
            Assert::assertArraySubset([
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
            ], $payload);
        });
    }

    /** @test */
    public function it_determines_if_a_task_is_filtered()
    {
        $task = $this->app->make(Schedule::class)
            ->command('thenpingme:filtered')
            ->description('This is the description')
            ->when(function () {
                return false;
            });

        tap(ThenpingmePayload::fromTask($task)->toArray(), function ($payload) use ($task) {
            Assert::assertArraySubset([
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
            ], $payload);
        });
    }

    /** @test */
    public function it_determines_if_a_job_runs_in_the_background()
    {
        $task = $this->app->make(Schedule::class)
            ->command('thenpingme:background')
            ->description('This is the description')
            ->runInBackground();

        tap(ThenpingmePayload::fromTask($task)->toArray(), function ($payload) use ($task) {
            Assert::assertArraySubset([
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
            ], $payload);
        });
    }

    /** @test */
    public function it_generates_a_setup_payload()
    {
        $scheduler = $this->app->make(Schedule::class);

        $events = ScheduledTaskCollection::make([
            $scheduler->command('thenpingme:first')->description('This is the first task'),
            $scheduler->command('thenpingme:second')->description('This is the second task'),
        ]);

        tap(ThenpingmeSetupPayload::make($events, 'super-secret')->toArray(), function ($payload) use ($events) {
            Assert::assertArraySubset([
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
                    ],
                ],
            ], $payload);
        });
    }

    /** @test */
    public function it_generates_the_correct_payload_for_a_scheduled_task_starting()
    {
        Carbon::setTestNow('2019-10-11 20:58:00', 'UTC');

        $event = new ScheduledTaskStarting(
            $this->app->make(Schedule::class)
                ->command('thenpingme:first')
                ->description('This is the first task')
                ->withoutOverlapping(10)
                ->onOneServer()
        );

        tap(ThenpingmePayload::fromEvent($event), function ($payload) {
            $this->assertInstanceOf(ScheduledTaskStartingPayload::class, $payload);

            tap($payload->toArray(), function ($body) use ($payload) {
                $this->assertEquals($payload->fingerprint(), $body['fingerprint']);
                $this->assertEquals('10.1.1.1', $body['ip']);
                $this->assertEquals(gethostname(), $body['hostname']);
                $this->assertEquals('ScheduledTaskStarting', $body['type']);
                $this->assertEquals('2019-10-11T20:58:00+00:00', $body['time']);
                $this->assertEquals('2019-10-11T21:08:00+00:00', $body['expires']);
                $this->assertEquals(app()->environment(), $body['environment']);
                $this->assertTrue($body['task']['without_overlapping']);
                $this->assertTrue($body['task']['on_one_server']);
                $this->assertArrayHasKey('memory', $body);
            });
        });
    }

    /** @test */
    public function it_correctly_identifies_ip_for_a_vapor_app()
    {
        Carbon::setTestNow('2019-10-11 20:58:00', 'UTC');

        $event = new ScheduledTaskStarting(
            $this->app->make(Schedule::class)
                ->command('thenpingme:first')
                ->description('This is the first task')
                ->withoutOverlapping(10)
                ->onOneServer()
        );

        $_ENV['VAPOR_SSM_PATH'] = '/some/lambda/path';

        tap(ThenpingmePayload::fromEvent($event), function ($payload) {
            tap($payload->toArray(), function ($body) use ($payload) {
                $this->assertEquals(ThenpingmePayload::getIp(gethostname()), $body['ip']);
            });
        });

        unset($_ENV['VAPOR_SSM_PATH']);
    }

    /** @test */
    public function it_includes_the_release_if_configured_to_do_so()
    {
        config(['thenpingme.release' => 'this is the release']);

        $event = new ScheduledTaskStarting(
            $this->app->make(Schedule::class)
                ->command('thenpingme:first')
                ->description('This is the first task')
                ->withoutOverlapping(10)
                ->onOneServer()
        );

        tap(ThenpingmePayload::fromEvent($event), function ($payload) {
            tap($payload->toArray(), function ($body) {
                $this->assertEquals('this is the release', $body['release']);
                $this->assertEquals('this is the release', $body['task']['release']);
            });
        });
    }

    /** @test */
    public function it_generates_the_correct_payload_for_a_scheduled_task_finished()
    {
        Carbon::setTestNow('2019-10-11 20:58:00', 'UTC');

        $event = new ScheduledTaskFinished(
            $this->app->make(Schedule::class)->command('thenpingme:first')->description('This is the first task'),
            1
        );

        tap(ThenpingmePayload::fromEvent($event), function ($payload) {
            $this->assertInstanceOf(ScheduledTaskFinishedPayload::class, $payload);

            tap($payload->toArray(), function ($body) use ($payload) {
                $this->assertEquals($payload->fingerprint(), $body['fingerprint']);
                $this->assertEquals('10.1.1.1', $body['ip']);
                $this->assertEquals(gethostname(), $body['hostname']);
                $this->assertEquals('ScheduledTaskFinished', $body['type']);
                $this->assertEquals('2019-10-11T20:58:00+00:00', $body['time']);
                $this->assertEquals('1', $body['runtime']);
                $this->assertNull($body['exit_code']);
                $this->assertEquals(app()->environment(), $body['environment']);
                $this->assertArrayHasKey('memory', $body);
            });
        });
    }

    /** @test */
    public function it_generates_the_correct_payload_for_a_scheduled_task_skipped()
    {
        Carbon::setTestNow('2019-10-11 20:58:00', 'UTC');

        $event = new ScheduledTaskSkipped(
            $this->app->make(Schedule::class)->command('thenpingme:first')->description('This is the first task'),
            1
        );

        tap(ThenpingmePayload::fromEvent($event), function ($payload) {
            $this->assertInstanceOf(ScheduledTaskSkippedPayload::class, $payload);

            tap($payload->toArray(), function ($body) use ($payload) {
                $this->assertEquals($payload->fingerprint(), $body['fingerprint']);
                $this->assertEquals('10.1.1.1', $body['ip']);
                $this->assertEquals(gethostname(), $body['hostname']);
                $this->assertEquals('ScheduledTaskSkipped', $body['type']);
                $this->assertEquals('2019-10-11T20:58:00+00:00', $body['time']);
                $this->assertEquals(app()->environment(), $body['environment']);
            });
        });
    }

    /** @test */
    public function it_generates_the_correct_payload_for_a_failed_scheduled_task()
    {
        Carbon::setTestNow('2019-10-11 20:58:00', 'UTC');

        $event = new ScheduledTaskFailed(
            $this->app->make(Schedule::class)->command('thenpingme:first')->description('This is the first task'),
            new Exception('Some exception has occurred')
        );

        tap(ThenpingmePayload::fromEvent($event), function ($payload) {
            $this->assertInstanceOf(ScheduledTaskFailedPayload::class, $payload);

            tap($payload->toArray(), function ($body) use ($payload) {
                $this->assertEquals($payload->fingerprint(), $body['fingerprint']);
                $this->assertEquals('10.1.1.1', $body['ip']);
                $this->assertEquals(gethostname(), $body['hostname']);
                $this->assertEquals('ScheduledTaskFailed', $body['type']);
                $this->assertEquals('2019-10-11T20:58:00+00:00', $body['time']);
                $this->assertEquals(app()->environment(), $body['environment']);
                $this->assertEquals('Some exception has occurred', $body['exception']);
            });
        });
    }

    /** @test */
    public function it_generates_a_sync_payload()
    {
        $schedule = $this->app->make(Schedule::class);

        $events = ScheduledTaskCollection::make([
            $schedule->command('thenpingme:first')->description('This is the first synced task'),
        ]);

        tap(SyncPayload::make($events)->toArray(), function ($payload) use ($events) {
            Assert::assertArraySubset([
                'project' => [
                    'uuid' => 'abc123',
                    'name' => 'We changed the project name',
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
            ], $payload);
        });
    }

    /** @test */
    public function it_identifies_ip_address()
    {
        $host = gethostname();

        // Vapor
        $_ENV['VAPOR_SSM_PATH'] = '/some/vapor/path';

        $this->assertEquals(gethostbyname($host), ThenpingmePayload::getIp($host));

        unset($_ENV['VAPOR_SSM_PATH']);

        // SERVER_ADDR is set
        putenv('SERVER_ADDR=10.11.12.13');

        $this->assertEquals('10.11.12.13', ThenpingmePayload::getIp($host));

        putenv('SERVER_ADDR');

        // Fallback
        if (($ip = gethostbyname($host)) !== '127.0.0.1') {
            $this->assertEquals($ip, ThenpingmePayload::getIp($host));
        } else {
            $this->assertNull(ThenpingmePayload::getIp($host));
        }
    }
}
