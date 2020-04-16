<?php

namespace Thenpingme\Collections;

use Illuminate\Support\Collection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\TaskPayload;

class ScheduledTaskCollection extends Collection
{
    public function nonUnique(): self
    {
        return static::make($this
            ->map(function ($task) {
                return TaskPayload::fromTask($task)->toArray();
            })
            ->groupBy('mutex')
            ->filter(function ($group) {
                return $group->count() > 1;
            })
            ->flatten(1)
            ->map(function ($task) {
                return [
                    'type' => $task['type'],
                    'expression' => $task['expression'],
                    'interval' => Thenpingme::translateExpression($task['expression']),
                    'description' => $task['description'],
                    'extra' => $task['type'] == 'closure'
                        ? sprintf('Line %s of %s', $task['extra']['line'], $task['extra']['file'])
                        : null,
                ];
            }));
    }

    public function hasNonUniqueJobs(): bool
    {
        return $this
            ->where('type', 'job')
            ->groupBy(function ($task) {
                return $task['expression'].$task['interval'].$task['description'];
            })
            ->filter(function ($group) {
                return $group->count() > 1;
            })
            ->isNotEmpty();
    }

    public function hasNonUniqueClosures(): bool
    {
        return $this
            ->where('type', 'closure')
            ->groupBy(function ($task) {
                return $task['expression'].$task['interval'].$task['description'];
            })
            ->filter(function ($group) {
                return $group->count() > 1;
            })
            ->isNotEmpty();
    }
}
