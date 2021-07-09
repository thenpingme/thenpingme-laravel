<?php

use Illuminate\Console\Scheduling\Schedule;
use Thenpingme\Payload\TaskPayload;
use Thenpingme\TaskIdentifier;
use Thenpingme\Tests\Fixtures\SomeJob;

it('identifies artisan commands', function () {
    $task = $this->app->make(Schedule::class)->command('thenpingme:test');

    expect(TaskPayload::make($task)->toArray())->toHaveKey('type', TaskIdentifier::TYPE_COMMAND);
});

it('identifies shell commands', function () {
    $task = $this->app->make(Schedule::class)->exec('echo "testing"');

    expect(TaskPayload::make($task)->toArray())->toHaveKey('type', TaskIdentifier::TYPE_SHELL);
});

it('identifies closures', function () {
    $task = $this->app->make(Schedule::class)->call(function () {
        echo 'testing';
    });

    expect(TaskPayload::make($task)->toArray())->toHaveKey('type', TaskIdentifier::TYPE_CLOSURE);

    $task = $this->app->make(Schedule::class)->call(function () {
        echo 'testing';
    })->description('some closure task');

    expect(TaskPayload::make($task)->toArray())->toHaveKey('type', TaskIdentifier::TYPE_CLOSURE);
});

it('identifies jobs', function () {
    $task = $this->app->make(Schedule::class)->job(SomeJob::class);

    expect(TaskPayload::make($task)->toArray())->toHaveKey('type', TaskIdentifier::TYPE_JOB);
});
