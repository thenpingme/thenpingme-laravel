<?php

namespace Thenpingme;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Str;

class TaskIdentifier
{
    const TYPE_CLOSURE = 'closure';

    const TYPE_COMMAND = 'command';

    const TYPE_JOB = 'job';

    const TYPE_SHELL = 'shell';

    public function __invoke($task)
    {
        if ($task instanceof CallbackEvent) {
            if (is_null($task->command) && $task->description && class_exists($task->description)) {
                return static::TYPE_JOB;
            }

            if (is_null($task->command) && Str::is($task->description, $task->getSummaryForDisplay())) {
                return static::TYPE_CLOSURE;
            }

            if (Str::is($task->getSummaryForDisplay(), 'Closure')) {
                return static::TYPE_CLOSURE;
            }
        }

        if ($task instanceof Event) {
            if (Str::contains($this->sanitisedCommand($task->command), 'artisan')) {
                return static::TYPE_COMMAND;
            }

            return static::TYPE_SHELL;
        }
    }

    private function sanitisedCommand(string $command): string
    {
        return trim(str_replace([
            "'",
            '"',
            PHP_BINARY,
        ], '', $command));
    }
}
