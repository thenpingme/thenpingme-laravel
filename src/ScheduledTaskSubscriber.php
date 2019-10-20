<?php

namespace Thenpingme;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Thenpingme\Client\Client;
use Thenpingme\Payload\ThenpingmePayload;

class ScheduledTaskSubscriber
{
    /**
     * @var \Thenpingme\Thenpingme
     */
    private $thenpingme;

    public function __construct(Client $thenpingme)
    {
        $this->thenpingme = $thenpingme;
    }

    public function handleScheduledTaskStarting(ScheduledTaskStarting $event): void
    {
        $this->thenpingme
            ->ping()
            ->payload(ThenpingmePayload::fromEvent($event)->toArray())
            ->dispatch();
    }

    public function handleScheduledTaskFinished(ScheduledTaskFinished $event): void
    {
        $this->thenpingme
            ->ping()
            ->payload(ThenpingmePayload::fromEvent($event)->toArray())
            ->dispatch();
    }

    public function subscribe($events): void
    {
        $events->listen(
            ScheduledTaskStarting::class,
            static::class.'@handleScheduledTaskStarting'
        );

        $events->listen(
            ScheduledTaskFinished::class,
            static::class.'@handleScheduledTaskFinished'
        );
    }
}
