<?php

use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Thenpingme\ThenpingmePingJob;

it('listens for scheduler events', function ($event, $args) {
    if (! class_exists($event)) {
        $this->markTestSkipped("{$event} class does not exist in this version of Laravel");
    }

    Config::set([
        'thenpingme.project_id' => 'abc123',
        'thenpingme.signing_key' => 'super-secret',
    ]);

    Queue::fake();

    $task = $this->app->make(Schedule::class)->command('thenpingme:testing');

    tap($this->app->make(Dispatcher::class), fn ($d) => $d->dispatch(new $event($task, ...$args)));

    Queue::assertPushed(ThenpingmePingJob::class, function ($job) {
        expect($job->url)->toBe('https://thenping.me/api/projects/abc123/ping');

        return true;
    });
})->with([
    'scheduled task starting' => [ScheduledTaskStarting::class, []],
    'scheduled task finished' => [ScheduledTaskFinished::class, [1]],
    'scheduled task skipped' => [ScheduledTaskSkipped::class, [1]],
    'scheduled task failed' => [ScheduledTaskFailed::class, [new Exception('testing')]],
    'scheduled backround task finished' => [ScheduledBackgroundTaskFinished::class, []],
]);
