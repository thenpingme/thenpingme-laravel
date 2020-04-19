<?php

namespace Thenpingme;

use Thenpingme\Client\Client;
use Thenpingme\Events\ScheduledTaskFinished;
use Thenpingme\Events\ScheduledTaskSkipped;
use Thenpingme\Events\ScheduledTaskStarting;
use Thenpingme\Payload\ThenpingmePayload;

class ScheduledTaskSubscriber
{
    /**
     * @var \Thenpingme\Client\Client
     */
    private $thenpingme;

    public function __construct(Client $thenpingme)
    {
        $this->thenpingme = $thenpingme;
    }

    public function handleScheduledTaskEvent($event): void
    {
        $this->thenpingme
            ->ping()
            ->payload(ThenpingmePayload::fromEvent($event)->toArray())
            ->dispatch();
    }

    public function subscribe($events): void
    {
        $events->listen(
            [
                ScheduledTaskStarting::class,
                ScheduledTaskFinished::class,
                ScheduledTaskSkipped::class,
            ],
            static::class.'@handleScheduledTaskEvent'
        );
    }
}
