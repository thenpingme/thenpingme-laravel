<?php

declare(strict_types=1);

namespace Thenpingme\Payload;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Stringable;
use Symfony\Component\Process\Process;
use Thenpingme\Facades\Thenpingme as ThenpingmeFacade;
use Thenpingme\Thenpingme;

abstract class ThenpingmePayload implements Arrayable
{
    /**
     * @param  mixed  $event
     */
    protected function __construct(protected $event)
    {
    }

    /**
     * @param  mixed  $event
     */
    public static function fromEvent($event): ?ThenpingmePayload
    {
        return match (true) {
            $event instanceof ScheduledTaskStarting => new ScheduledTaskStartingPayload($event),
            $event instanceof ScheduledTaskFinished => new ScheduledTaskFinishedPayload($event),
            $event instanceof ScheduledTaskSkipped => new ScheduledTaskSkippedPayload($event),
            $event instanceof ScheduledTaskFailed => new ScheduledTaskFailedPayload($event),
            default => null,
        };
    }

    public function fingerprint(): string
    {
        return sha1(vsprintf('%s.%s.%s.%s.%s', [
            Config::get('thenpingme.project_id'),
            ThenpingmeFacade::fingerprintTask($this->event->task),
            getmypid(),
            spl_object_id($this->event->task),
            spl_object_hash($this->event->task),
        ]));
    }

    public function toArray(): array
    {
        return array_merge(
            array_filter([
                'thenpingme' => [
                    'version' => ThenpingmeFacade::version(),
                ],
                'release' => Config::get('thenpingme.release'),
                'fingerprint' => $this->fingerprint(),
                'hostname' => $hostname = gethostname(),
                'ip' => static::getIp($hostname),
                'environment' => app()->environment(),
                'project' => array_filter([
                    'uuid' => Config::get('thenpingme.project_id'),
                    'name' => Config::get('thenpingme.project_name'),
                    'release' => Config::get('thenpingme.release'),
                    'timezone' => Carbon::now()->getTimezone()->toOffsetName(),
            ]),
        ]),
            $this->shouldLogOutput()
            ? ['output' => $this->getOutput()->toString()]
            : []
        );
    }

    protected function shouldLogOutput(): bool
    {
        if (is_null($output = data_get($this->event, 'task.thenpingmeOptions.output'))) {
            return false;
        }

        if ((($output & Thenpingme::STORE_OUTPUT_IF_PRESENT) === Thenpingme::STORE_OUTPUT_IF_PRESENT)) {
            if ($this->getOutput()->isEmpty()) {
                return false;
            }
        }

        return true;
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

    protected function getOutput(): Stringable
    {
        $storeOutput = data_get($this->event, 'task.thenpingmeOptions.output');
        $exitCode = data_get($this->event, 'task.exitCode');

        if (is_null($storeOutput) || class_basename($this->event) === 'ScheduledTaskStarting') {
            return new Stringable;
        }

        $output = function (): Stringable {
            if (! is_null($path = data_get($this->event, 'task.output')) && is_file($path)) {
                $contents = trim(file_get_contents($path));
            }

            return new Stringable($contents ?? '');
        };

        return match(true) {
            (($storeOutput & Thenpingme::STORE_OUTPUT) === Thenpingme::STORE_OUTPUT) => $output(),
            ((($storeOutput & Thenpingme::STORE_OUTPUT_ON_SUCCESS) === Thenpingme::STORE_OUTPUT_ON_SUCCESS) && ($exitCode === 0)) => $output(),
            ((($storeOutput & Thenpingme::STORE_OUTPUT_ON_FAILURE) === Thenpingme::STORE_OUTPUT_ON_FAILURE) && ($exitCode !== 0)) => $output(),
            default => new Stringable,
        };
    }
}
