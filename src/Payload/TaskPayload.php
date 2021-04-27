<?php

namespace Thenpingme\Payload;

use Illuminate\Support\Facades\Date;
use ReflectionClass;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\TaskIdentifier;

class TaskPayload extends ThenpingmePayload
{
    public $task;

    protected function __construct($task)
    {
        $this->task = $task;
    }

    public static function make($task): self
    {
        return new static($task);
    }

    public function toArray(): array
    {
        return [
            'timezone' => Date::now($this->task->timezone)->getOffsetString(),
            'release' => config('thenpingme.release'),
            'type' => (new TaskIdentifier)($this->task),
            'expression' => $this->task->expression,
            'command' => $this->sanitisedCommand(),
            'maintenance' => $this->task->evenInMaintenanceMode,
            'without_overlapping' => $this->task->withoutOverlapping,
            'on_one_server' => $this->task->onOneServer,
            'run_in_background' => $this->task->runInBackground,
            'description' => $this->task->description,
            'mutex' => Thenpingme::fingerprintTask($this->task),
            'filtered' => $this->isFiltered(),
            'extra' => $this->task->extra ?? null,
        ];
    }

    private function isFiltered(): bool
    {
        return with(new ReflectionClass($this->task), function ($class) {
            return ! empty(array_merge(
                tap($class->getProperty('filters'))->setAccessible(true)->getValue($this->task),
                tap($class->getProperty('rejects'))->setAccessible(true)->getValue($this->task)
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
        ], '', $this->task->command));
    }
}
