<?php

namespace Thenpingme\Payload;

use Illuminate\Contracts\Support\Arrayable;
use Thenpingme\Events\ScheduledTaskFinished;
use Thenpingme\Events\ScheduledTaskSkipped;
use Thenpingme\Events\ScheduledTaskStarting;
use Thenpingme\Facades\Thenpingme;

abstract class ThenpingmePayload implements Arrayable
{
    protected $event;

    protected $tasks;

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

    public function fingerprint(): string
    {
        return sha1(vsprintf('%s.%s.%s', [
            config('thenpingme.project_id'),
            Thenpingme::fingerprintTask($this->event->task),
            getmypid(),
        ]));
    }

    public function toArray(): array
    {
        return array_filter([
            'release' => config('thenpingme.release'),
            'fingerprint' => $this->fingerprint(),
            'ip' => request()->server('SERVER_ADDR'),
            'project' => array_filter([
                'uuid' => config('thenpingme.project_id'),
                'name' => config('app.name'),
                'release' => config('thenpingme.release'),
            ]),
        ]);
    }
}
