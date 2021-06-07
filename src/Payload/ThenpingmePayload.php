<?php

namespace Thenpingme\Payload;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\Process;
use Thenpingme\Facades\Thenpingme;

abstract class ThenpingmePayload implements Arrayable
{
    protected $event;

    protected function __construct($event)
    {
        $this->event = $event;
    }

    public static function fromEvent($event): ?ThenpingmePayload
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

        if ($event instanceof ScheduledTaskFailed) {
            return new ScheduledTaskFailedPayload($event);
        }

        return null;
    }

    public static function fromTask(Event $task): TaskPayload
    {
        return TaskPayload::make($task);
    }

    public function fingerprint(): string
    {
        return sha1(vsprintf('%s.%s.%s.%s.%s', [
            config('thenpingme.project_id'),
            Thenpingme::fingerprintTask($this->event->task),
            getmypid(),
            spl_object_id($this->event->task),
            spl_object_hash($this->event->task),
        ]));
    }

    public function toArray(): array
    {
        return array_filter([
            'release' => config('thenpingme.release'),
            'fingerprint' => $this->fingerprint(),
            'hostname' => $hostname = gethostname(),
            'ip' => static::getIp($hostname),
            'environment' => app()->environment(),
            'project' => array_filter([
                'uuid' => config('thenpingme.project_id'),
                'name' => config('app.name'),
                'release' => config('thenpingme.release'),
                'timezone' => Carbon::now()->timezone->toOffsetName(),
            ]),
        ]);
    }

    public static function getIp(string $hostname): ?string
    {
        // If this is Vapor
        if (isset($_ENV['VAPOR_SSM_PATH'])) {
            return gethostbyname($hostname);
        }

        if ($ip = getenv('SERVER_ADDR')) {
            return $ip;
        }

        // I don't really know the best way to test this... but it should be fine.
        if (PHP_OS == 'Linux') {
            return trim(Arr::first(
                explode(' ', tap(new Process(['hostname', '-I']), function (Process $process) {
                    $process->run();
                })->getOutput())
            ));
        }

        if (($ip = gethostbyname($hostname)) !== '127.0.0.1') {
            return $ip;
        }

        return null;
    }
}
