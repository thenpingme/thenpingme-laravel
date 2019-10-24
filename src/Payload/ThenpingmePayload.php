<?php

namespace Thenpingme\Payload;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Thenpingme\TaskIdentifier;

abstract class ThenpingmePayload implements Arrayable
{
    protected $event;

    protected function __construct($event)
    {
        $this->event = $event;
    }

    public static function fromEvent($event): self
    {
        if ($event instanceof ScheduledTaskStarting) {
            return new ScheduledTaskStartingPayload($event);
        }

        if ($event instanceof ScheduledTaskFinished) {
            return new ScheduledTaskFinishedPayload($event);
        }

        if ($event instanceof ScheduledTaskSkipped) {
            return new ScheduledTaskSkippedPayload($event);
        }
    }

    public static function fromTask($task): self
    {
        return TaskPayload::make($task);
    }

    public function toArray(): array
    {
        return [];
    }
}
