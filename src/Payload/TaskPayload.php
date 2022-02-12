<?php

declare(strict_types=1);

namespace Thenpingme\Payload;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use ReflectionClass;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Makeable;
use Thenpingme\TaskIdentifier;

final class TaskPayload
{
    use Makeable;

    protected string $taskType;

    protected function __construct(private Event $schedulingEvent)
    {
        $this->taskType = (new TaskIdentifier)->__invoke($this->schedulingEvent);
    }

    public function toArray(): array
    {
        $fingerprint = Thenpingme::fingerprintTask($this->schedulingEvent);

        return [
            'timezone' => Date::now($this->schedulingEvent->timezone)->getOffsetString(),
            'release' => Config::get('thenpingme.release'),
            'type' => $this->taskType,
            'expression' => $this->schedulingEvent->expression,
            'command' => $this->sanitisedCommand(),
            'maintenance' => $this->schedulingEvent->evenInMaintenanceMode,
            'without_overlapping' => $this->schedulingEvent->withoutOverlapping,
            'on_one_server' => $this->schedulingEvent->onOneServer,
            'run_in_background' => $this->schedulingEvent->runInBackground,
            'description' => $this->schedulingEvent->description,
            'mutex' => $fingerprint,
            'filtered' => $this->isFiltered(),
            /* @phpstan-ignore-next-line */
            'extra' => $this->schedulingEvent->extra ?? null,
            'grace_period' => data_get($this->schedulingEvent, 'thenpingmeOptions.grace_period'),
            'allowed_run_time' => data_get($this->schedulingEvent, 'thenpingmeOptions.allowed_run_time'),
            'notify_after_consecutive_alerts' => data_get($this->schedulingEvent, 'thenpingmeOptions.notify_after_consecutive_alerts'),
        ];
    }

    private function isFiltered(): bool
    {
        $class = new ReflectionClass($this->schedulingEvent);

        return ! empty(array_merge(
            tap($class->getProperty('filters'))->setAccessible(true)->getValue($this->schedulingEvent),
            tap($class->getProperty('rejects'))->setAccessible(true)->getValue($this->schedulingEvent)
        ));
    }

    private function sanitisedCommand(): string
    {
        if ($this->taskType === TaskIdentifier::TYPE_CLOSURE &&
            blank($this->schedulingEvent->command) &&
            blank($this->schedulingEvent->description)
        ) {
            return vsprintf('%s:%s', [
                data_get($this->schedulingEvent, 'extra.file'),
                data_get($this->schedulingEvent, 'extra.line'),
            ]);
        }

        return trim(str_replace([
            "'",
            '"',
            PHP_BINARY,
            'artisan',
        ], '', $this->schedulingEvent->command ?: ''));
    }
}
