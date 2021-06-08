<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Scheduling\Schedule;
use Thenpingme\Payload\TaskPayload;
use Thenpingme\TaskIdentifier;
use Thenpingme\Tests\Fixtures\SomeJob;

class TaskIdentifierTest extends TestCase
{
    /** @test */
    public function it_identifies_artisan_commands()
    {
        $task = $this->app->make(Schedule::class)->command('thenpingme:test');

        tap(TaskPayload::make($task)->toArray(), function (array $payload) {
            $this->assertEquals(TaskIdentifier::TYPE_COMMAND, $payload['type']);
        });
    }

    /** @test */
    public function it_identifies_shell_commands()
    {
        $task = $this->app->make(Schedule::class)->exec('echo "testing"');

        tap(TaskPayload::make($task)->toArray(), function (array $payload) {
            $this->assertEquals(TaskIdentifier::TYPE_SHELL, $payload['type']);
        });
    }

    /** @test */
    public function it_identifies_closures()
    {
        $task = $this->app->make(Schedule::class)->call(function () {
            echo 'testing';
        });

        tap(TaskPayload::make($task)->toArray(), function (array $payload) {
            $this->assertEquals(TaskIdentifier::TYPE_CLOSURE, $payload['type']);
        });

        $task = $this->app->make(Schedule::class)->call(function () {
            echo 'testing';
        })->description('some closure task');

        tap(TaskPayload::make($task)->toArray(), function (array $payload) {
            $this->assertEquals(TaskIdentifier::TYPE_CLOSURE, $payload['type']);
        });
    }

    /** @test */
    public function it_identifies_jobs()
    {
        $task = $this->app->make(Schedule::class)->job(SomeJob::class);

        tap(TaskPayload::make($task)->toArray(), function (array $payload) {
            $this->assertEquals(TaskIdentifier::TYPE_JOB, $payload['type']);
        });
    }
}
