<?php

namespace Thenpingme\Payload;

use Illuminate\Support\Facades\Date;
use ReflectionClass;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\TaskIdentifier;

class TaskPayload extends ThenpingmePayload
{
    public $task;

    public $taskType;

    protected function __construct($task)
    {
        $this->task = $task;

        $this->taskType = (new TaskIdentifier)->__invoke($this->task);
    }

    public static function make($task): self
    {
        return new static($task);
    }

    public function toArray(): array
    {
        $fingerprint = Thenpingme::fingerprintTask($this->task);

        return [
            'timezone' => Date::now($this->task->timezone)->getOffsetString(),
            'release' => config('thenpingme.release'),
            'type' => $this->taskType,
            'expression' => $this->task->expression,
            'command' => $this->sanitisedCommand(),
            'maintenance' => $this->task->evenInMaintenanceMode,
            'without_overlapping' => $this->task->withoutOverlapping,
            'on_one_server' => $this->task->onOneServer,
            'run_in_background' => $this->task->runInBackground,
            'description' => $this->task->description,
            'mutex' => $fingerprint,
            'filtered' => $this->isFiltered(),
            'extra' => $this->task->extra ?? null,
        ];
    }

    private function isFiltered(): bool
    {
        return with(new ReflectionClass($this->task), function ($class) {
            return ! empty(array_merge(
                tap($class->getProperty('filters'))->setAccessible(true)->getValue($this->task),
                tap($class->getProperty('rejects'))->setAccessible(true)->getValue($this->task),
            ));
        });
    }

    private function sanitisedCommand(): string
    {
        if ($this->taskType === TaskIdentifier::TYPE_CLOSURE &&
            blank($this->task->command) &&
            blank($this->task->description)
        ) {
            return vsprintf('%s:%s', [
                data_get($this->task, 'extra.file'),
                data_get($this->task, 'extra.line'),
            ]);
        }

        return trim(str_replace([
            "'",
            '"',
            PHP_BINARY,
            'artisan',
        ], '', $this->task->command));
    }
}
