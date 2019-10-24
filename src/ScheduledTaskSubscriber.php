<?php

namespace Thenpingme;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
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
