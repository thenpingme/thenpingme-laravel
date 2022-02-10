<?php

declare(strict_types=1);

namespace Thenpingme\Collections;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Collection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\TaskPayload;

class ScheduledTaskCollection extends Collection
{
    public function collisions(): ScheduledTaskCollection
    {
        return static::make($this
            ->map(function (Event $task) {
                return TaskPayload::make($task)->toArray();
            })
            ->groupBy('mutex')
            ->filter(function (ScheduledTaskCollection $group) {
                return $group->count() > 1;
            })
            ->flatten(1)
            ->map(function (array $task) {
                return [
                    'mutex' => $task['mutex'],
                    'type' => $task['type'],
                    'command' => $command = $task['command'] ?: $task['description'],
                    'expression' => $task['expression'],
                    'interval' => Thenpingme::translateExpression($task['expression']),
                    'description' => $task['description'] !== $command ? $task['description'] : null,
                    'extra' => $task['type'] == 'closure' && isset($task['extra'])
                        ? sprintf('Line %s of %s', $task['extra']['line'], $task['extra']['file'])
                        : null,
                ];
            }));
    }

    public function hasNonUniqueJobs(): bool
    {
        return $this
            ->where('type', 'job')
            ->groupBy(function (array $task) {
                return $task['expression'].$task['interval'].$task['description'];
            })
            ->filter(function (ScheduledTaskCollection $group) {
                return $group->count() > 1;
            })
            ->isNotEmpty();
    }

    public function hasNonUniqueClosures(): bool
    {
        return $this
            ->where('type', 'closure')
            ->groupBy(function (array $task) {
                return $task['expression'].$task['interval'].$task['description'];
            })
            ->filter(function (ScheduledTaskCollection $group) {
                return $group->count() > 1;
            })
            ->isNotEmpty();
    }
}
