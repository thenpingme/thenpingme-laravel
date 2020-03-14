<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Thenpingme\Payload\ThenpingmePayload;
use Thenpingme\TaskIdentifier;

class TaskIdentifierTest extends TestCase
{
    /** @test */
    public function it_identifies_artisan_commands()
    {
        $task = app(Schedule::class)->command('thenpingme:test');

        tap(ThenpingmePayload::fromTask($task)->toArray(), function ($payload) {
            $this->assertEquals(TaskIdentifier::TYPE_COMMAND, $payload['type']);
        });
    }

    /** @test */
    public function it_identifies_shell_commands()
    {
        $task = app(Schedule::class)->exec('echo "testing"');

        tap(ThenpingmePayload::fromTask($task)->toArray(), function ($payload) {
            $this->assertEquals(TaskIdentifier::TYPE_SHELL, $payload['type']);
        });
    }

    /** @test */
    public function it_identifies_closures()
    {
        $task = app(Schedule::class)->call(function () {
            echo 'testing';
        });

        tap(ThenpingmePayload::fromTask($task)->toArray(), function ($payload) {
            $this->assertEquals(TaskIdentifier::TYPE_CLOSURE, $payload['type']);
        });

        $task = app(Schedule::class)->call(function () {
            echo 'testing';
        })->description('some closure task');

        tap(ThenpingmePayload::fromTask($task)->toArray(), function ($payload) {
            $this->assertEquals(TaskIdentifier::TYPE_CLOSURE, $payload['type']);
        });
    }

    /** @test */
    public function it_identifies_jobs()
    {
        $task = app(Schedule::class)->job('Thenpingme\Tests\SomeJob');

        tap(ThenpingmePayload::fromTask($task)->toArray(), function ($payload) {
            $this->assertEquals(TaskIdentifier::TYPE_JOB, $payload['type']);
        });
    }
}
