<?php

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Thenpingme\Thenpingme;
use Thenpingme\ThenpingmePingJob;

beforeEach(function () {
    Config::set([
        'thenpingme.project_id' => 'abc123',
        'thenpingme.signing_key' => 'super-secret',
    ]);

    Queue::fake();
});

it('logs scheduled task output', function (int $outputType, string $output) {
    Event::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event) {
        expect($event->task->output)->not->toBe('/dev/null');
    });

    $this->app->make(Schedule::class)->exec("echo 'some output'")->thenpingme(
        output: $outputType
    );

    $this->artisan('schedule:run');

    Queue::assertPushed(function (ThenpingmePingJob $job) use ($output) {
        return Arr::get($job->payload, 'type') === 'ScheduledTaskFinished'
            && Arr::has($job->payload, 'output')
            && Arr::get($job->payload, 'output') === $output;
    });
})->with([
    'All output' => [Thenpingme::STORE_OUTPUT, "some output\n"],
    'Success output' => [Thenpingme::STORE_OUTPUT_ON_SUCCESS, "some output\n"],
    'Failure output' => [Thenpingme::STORE_OUTPUT_ON_FAILURE, ''],
]);

it('logs failure output', function () {
    Event::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event) {
        expect($event->task->output)->not->toBe('/dev/null');
    });

    $this->app->make(Schedule::class)->exec('somecommandthatdoesnotexist')->thenpingme(
        output: Thenpingme::STORE_OUTPUT_ON_FAILURE
    );

    $this->artisan('schedule:run');

    Queue::assertPushed(function (ThenpingmePingJob $job) {
        return Arr::get($job->payload, 'type') === 'ScheduledTaskFinished'
            && Arr::has($job->payload, 'output')
            && Str::of(Arr::get($job->payload, 'output'))->contains('somecommandthatdoesnotexist: command not found')
            && Arr::get($job->payload, 'exit_code') !== 1;
    });
});

it('only logs failure output if configured to do so', function (int $outputType, string $expected) {
    Event::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event) {
        expect($event->task->output)->not->toBe('/dev/null');
    });

    $this->app->make(Schedule::class)->exec('somecommandthatdoesnotexist')->thenpingme(
        output: $outputType
    );

    $this->artisan('schedule:run');

    Queue::assertPushed(function (ThenpingmePingJob $job) use ($expected) {
        $output = Arr::get($job->payload, 'output');

        return Arr::get($job->payload, 'type') === 'ScheduledTaskFinished'
            && blank($expected) ? $output === $expected : Str::of($output)->contains($expected)
            && Arr::get($job->payload, 'exit_code') !== 0;
    });
})->with([
    'All output' => [Thenpingme::STORE_OUTPUT, 'somecommandthatdoesnotexist: command not found'],
    'Success output' => [Thenpingme::STORE_OUTPUT_ON_SUCCESS, ''],
    'Failure output' => [Thenpingme::STORE_OUTPUT_ON_FAILURE, 'somecommandthatdoesnotexist: command not found'],
]);

it('does not log task output unless configured to do so', function () {
    Event::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event) {
        expect($event->task->output)->toBe('/dev/null');
    });

    $this->app->make(Schedule::class)->command(TestCommand::class);

    $this->artisan('schedule:run');

    Queue::assertPushed(function (ThenpingmePingJob $job) {
        return Arr::get($job->payload, 'type') === 'ScheduledTaskFinished'
            && Arr::has($job->payload, 'output') === false;
    });
});
