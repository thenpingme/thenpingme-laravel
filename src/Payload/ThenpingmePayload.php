<?php

namespace Thenpingme\Payload;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Symfony\Component\Process\Process;
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
        return sha1(vsprintf('%s.%s.%s.%s.%s', [
            config('thenpingme.project_id'),
            Thenpingme::fingerprintTask($this->event->task),
            getmypid(),
            spl_object_id($this->event),
            spl_object_hash($this->event),
        ]));
    }

    public function toArray(): array
    {
        return array_filter([
            'release' => config('thenpingme.release'),
            'fingerprint' => $this->fingerprint(),
            'ip' => $this->getIp(),
            'project' => array_filter([
                'uuid' => config('thenpingme.project_id'),
                'name' => config('app.name'),
                'release' => config('thenpingme.release'),
            ]),
        ]);
    }

    public function getIp(): ?string
    {
        // If this is Vapor
        if (isset($_ENV['VAPOR_SSM_PATH'])) {
            return gethostbyname(gethostname());
        }

        if (isset($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }

        // I don't really know the best way to test this... but it should be fine.
        if (PHP_OS == 'Linux') {
            return trim(Arr::first(
                explode(' ', tap(new Process(['hostname', '-I']), fn ($p) => $p->run())->getOutput())
            ));
        }

        return null;
    }
}
