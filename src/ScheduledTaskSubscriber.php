<?php

declare(strict_types=1);

namespace Thenpingme;

use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Events\Dispatcher;
use Thenpingme\Client\Client;
use Thenpingme\Payload\ThenpingmePayload;

class ScheduledTaskSubscriber
{
    public function __construct(private Client $thenpingme)
    {
    }

    /**
     * @param  mixed  $event
     */
    public function handleScheduledTaskEvent($event): void
    {
        $this
            ->thenpingme
            ->ping()
            ->payload(is_null($payload = ThenpingmePayload::fromEvent($event)) ? [] : $payload->toArray())
            ->dispatch();
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            [
                ScheduledTaskStarting::class,
                ScheduledTaskFinished::class,
                ScheduledTaskSkipped::class,
            ],
            static::class.'@handleScheduledTaskEvent'
        );

        if (class_exists(ScheduledTaskFailed::class)) {
            $events->listen(
                [
                    ScheduledTaskFailed::class,
                ],
                static::class.'@handleScheduledTaskEvent'
            );
        }

        if (class_exists(ScheduledBackgroundTaskFinished::class)) {
            $events->listen([
                ScheduledBackgroundTaskFinished::class,
            ], static::class.'@handleScheduledTaskEvent');
        }
    }
}
