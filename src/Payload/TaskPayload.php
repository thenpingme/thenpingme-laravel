<?php

declare(strict_types=1);

namespace Thenpingme\Payload;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Date;
use ReflectionClass;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Makeable;
use Thenpingme\TaskIdentifier;

final class TaskPayload
{
    use Makeable;

    private Event $schedulingEvent;

    protected function __construct(Event $schedulingEvent)
    {
        $this->schedulingEvent = $schedulingEvent;
    }

    public function toArray(): array
    {
        return [
            'timezone' => Date::now($this->schedulingEvent->timezone)->getOffsetString(),
            'release' => config('thenpingme.release'),
            'type' => (new TaskIdentifier)($this->schedulingEvent),
            'expression' => $this->schedulingEvent->expression,
            'command' => $this->sanitisedCommand(),
            'maintenance' => $this->schedulingEvent->evenInMaintenanceMode,
            'without_overlapping' => $this->schedulingEvent->withoutOverlapping,
            'on_one_server' => $this->schedulingEvent->onOneServer,
            'run_in_background' => $this->schedulingEvent->runInBackground,
            'description' => $this->schedulingEvent->description,
            'mutex' => Thenpingme::fingerprintTask($this->schedulingEvent),
            'filtered' => $this->isFiltered(),
            /* @phpstan-ignore-next-line */
            'extra' => $this->schedulingEvent->extra ?? null,
        ];
    }

    private function isFiltered(): bool
    {
        return with(new ReflectionClass($this->schedulingEvent), function (ReflectionClass $class) {
            return ! empty(array_merge(
                tap($class->getProperty('filters'))->setAccessible(true)->getValue($this->schedulingEvent),
                tap($class->getProperty('rejects'))->setAccessible(true)->getValue($this->schedulingEvent)
            ));
        });
    }

    private function sanitisedCommand(): string
    {
        return trim(str_replace([
            "'",
            '"',
            PHP_BINARY,
            'artisan',
        ], '', $this->schedulingEvent->command ?: ''));
    }
}
