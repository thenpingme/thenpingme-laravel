<?php

namespace Thenpingme\Tests\Payload;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Thenpingme\Payload\ScheduledTaskFinishedPayload;
use Thenpingme\Payload\ScheduledTaskStartingPayload;
use Thenpingme\Payload\ThenpingmePayload;
use Thenpingme\Payload\ThenpingmeSetupPayload;
use Thenpingme\Tests\TestCase;

class ThenpingmePayloadTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set([
            'app.name' => 'We changed the project name',
            'thenpingme.project_id' => 'abc123',
            'thenpingme.signing_key' => 'super-secret',
        ]);
    }

    /** @test */
    public function it_generates_a_task_payload()
    {
        $task = (new Event($this->mock(EventMutex::class), 'artisan generate:payload', 'UTC'))
            ->description('This is the description');

        tap(ThenpingmePayload::fromTask($task)->toArray(), function ($payload) {
            $this->assertEquals([
                'expression' => '* * * * *',
                'command' => 'generate:payload',
                'timezone' => 'UTC',
                'maintenance' => false,
                'without_overlapping' => false,
                'on_one_server' => false,
                'description' => 'This is the description',
            ], $payload);
        });
    }

    /** @test */
    public function it_generates_a_setup_payload()
    {
        $events = [
            (new Event($this->mock(EventMutex::class), 'artisan thenpingme:first', 'UTC'))->description('This is the first task'),
            (new Event($this->mock(EventMutex::class), 'artisan thenpingme:second', 'UTC'))->description('This is the second task'),
        ];

        tap(ThenpingmeSetupPayload::make($events)->toArray(), function ($payload) {
            $this->assertEquals([
               'project' => [
                   'uuid' => 'abc123',
                   'name' => 'We changed the project name',
                   'signing_key' => 'super-secret',
               ],
               'tasks' => [
                   [
                       'expression' => '* * * * *',
                       'command' => 'thenpingme:first',
                       'timezone' => 'UTC',
                       'maintenance' => false,
                       'without_overlapping' => false,
                       'on_one_server' => false,
                       'description' => 'This is the first task',
                   ],
                   [
                       'expression' => '* * * * *',
                       'command' => 'thenpingme:second',
                       'timezone' => 'UTC',
                       'maintenance' => false,
                       'without_overlapping' => false,
                       'on_one_server' => false,
                       'description' => 'This is the second task',
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
            (new Event($this->mock(EventMutex::class), 'artisan thenpingme:first', 'UTC'))
                ->description('This is the first task')
                ->withoutOverlapping(10)
                ->onOneServer()
        );

        tap(ThenpingmePayload::fromEvent($event), function ($payload) {
            $this->assertInstanceOf(ScheduledTaskStartingPayload::class, $payload);

            tap($payload->toArray(), function ($body) {
                $this->assertEquals('starting', $body['type']);
                $this->assertEquals('2019-10-11T20:58:00+00:00', $body['time']);
                $this->assertEquals('2019-10-11T21:08:00+00:00', $body['expires']);
                $this->assertTrue($body['without_overlapping']);
                $this->assertTrue($body['on_one_server']);
            });
        });
    }

    /** @test */
    public function it_generates_the_correct_payload_for_a_scheduled_task_finished()
    {
        Carbon::setTestNow('2019-10-11 20:58:00', 'UTC');

        $event = new ScheduledTaskFinished(
            (new Event($this->mock(EventMutex::class), 'artisan thenpingme:first', 'UTC'))->description('This is the first task'),
            1
        );

        tap(ThenpingmePayload::fromEvent($event), function ($payload) {
            $this->assertInstanceOf(ScheduledTaskFinishedPayload::class, $payload);

            tap($payload->toArray(), function ($body) {
                $this->assertEquals('finished', $body['type']);
                $this->assertEquals('2019-10-11T20:58:00+00:00', $body['time']);
                $this->assertEquals('1.00s', $body['runtime']);
                $this->assertNull($body['exit_code']);
            });
        });
    }
}
