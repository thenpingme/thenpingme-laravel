<?php

namespace Thenpingme\Payload;

use Illuminate\Support\Str;
use ReflectionClass;
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
            'type' => (new TaskIdentifier)($this->task),
            'expression' => $this->task->expression,
            'command' => ltrim(Str::after($this->task->command, 'artisan'), "', '"),
            'timezone' => $this->task->timezone,
            'maintenance' => $this->task->evenInMaintenanceMode,
            'without_overlapping' => $this->task->withoutOverlapping,
            'on_one_server' => $this->task->onOneServer,
            'description' => $this->task->description,
            'mutex' => $this->task->mutexName(),
            'filtered' => $this->isFiltered(),
        ];
    }

    private function isFiltered()
    {
        return with(new ReflectionClass($this->task), function ($class) {
            $filters = $class->getProperty('filters');
            $filters->setAccessible(true);

            return ! empty($filters->getValue($this->task));
        });
    }
}
