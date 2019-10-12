<?php

namespace Thenpingme\Payload;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class ThenpingmePayload implements Arrayable
{
    protected $event;

    private function __construct($event)
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

        return new static($event);
    }

    public static function fromTask($task): self
    {
        return new static((object) ['task' => $task]);
    }

    public function toArray(): array
    {
        $task = $this->event->task;

        return [
            'expression' => $task->expression,
            'command' => ltrim(Str::after($task->command, 'artisan')),
            'timezone' => $task->timezone,
            'maintenance' => $task->evenInMaintenanceMode,
            'without_overlapping' => $task->withoutOverlapping,
            'on_one_server' => $task->onOneServer,
            'description' => $task->description,
        ];
    }
}
