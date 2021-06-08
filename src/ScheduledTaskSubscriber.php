<?php

declare(strict_types=1);

namespace Thenpingme;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Events\Dispatcher;
use Thenpingme\Client\Client;
use Thenpingme\Payload\ThenpingmePayload;

class ScheduledTaskSubscriber
{
    private Client $thenpingme;

    public function __construct(Client $thenpingme)
    {
        $this->thenpingme = $thenpingme;
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
    }
}
