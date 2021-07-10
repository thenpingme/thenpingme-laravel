<?php

declare(strict_types=1);

namespace Thenpingme;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Str;

final class TaskIdentifier
{
    public const TYPE_CLOSURE = 'closure';

    public const TYPE_COMMAND = 'command';

    public const TYPE_JOB = 'job';

    public const TYPE_SHELL = 'shell';

    public function __invoke(Event $task)
    {
        if ($task instanceof CallbackEvent) {
            if (Str::of($task->command)->isEmpty() && $task->description && class_exists($task->description)) {
                return self::TYPE_JOB;
            }

            if (Str::of($task->command)->isEmpty() && Str::is($task->description, $task->getSummaryForDisplay())) {
                return self::TYPE_CLOSURE;
            }

            if (Str::of($task->getSummaryForDisplay())->is(['Closure', 'Callback'])) {
                return self::TYPE_CLOSURE;
            }
        }

        if (Str::contains($this->sanitisedCommand($task->command), 'artisan')) {
            return self::TYPE_COMMAND;
        }

        return self::TYPE_SHELL;
    }

    private function sanitisedCommand(?string $command): string
    {
        return trim(str_replace([
            "'",
            '"',
            PHP_BINARY,
        ], '', $command ?: ''));
    }
}
