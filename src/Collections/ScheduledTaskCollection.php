<?php

declare(strict_types=1);

namespace Thenpingme\Collections;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Collection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\TaskPayload;

class ScheduledTaskCollection extends Collection
{
    public function __construct($items = [])
    {
        parent::__construct(array_filter(
            $this->getArrayableItems($items),
            fn ($event) => ! isset($event->thenpingmeOptions) || $event->thenpingmeOptions['skip'] === false)
        );
    }

    public function collisions(): ScheduledTaskCollection
    {
        return ScheduledTaskCollection::make($this
            ->map(fn (Event $task) => TaskPayload::make($task)->toArray())
            ->groupBy('mutex')
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->flatten(1)
            ->map(fn (array $task) => [
                'mutex' => $task['mutex'],
                'type' => $task['type'],
                'command' => $command = $task['command'] ?: $task['description'],
                'expression' => $task['expression'],
                'interval' => Thenpingme::translateExpression($task['expression']),
                'description' => $task['description'] !== $command ? $task['description'] : null,
                'extra' => $task['type'] == 'closure' && isset($task['extra'])
                    ? sprintf('Line %s of %s', $task['extra']['line'], $task['extra']['file'])
                    : null,
            ]));
    }

    public function hasNonUniqueJobs(): bool
    {
        return $this
            ->where('type', 'job')
            ->groupBy(fn (array $task) => $task['expression'].$task['interval'].$task['description'])
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->isNotEmpty();
    }

    public function hasNonUniqueClosures(): bool
    {
        return $this
            ->where('type', 'closure')
            ->groupBy(fn (array $task) => $task['expression'].$task['interval'].$task['description'])
            ->filter(fn (Collection $group): bool => $group->count() > 1)
            ->isNotEmpty();
    }
}
