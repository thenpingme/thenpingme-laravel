<?php

namespace Thenpingme\Payload;

use Illuminate\Support\Carbon;

class ScheduledTaskFinishedPayload extends ThenpingmePayload
{
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => class_basename($this->event),
            'time' => Carbon::now()->toIso8601String(),
            'runtime' => $this->event->runtime,
            'exit_code' => $this->event->task->exitCode,
            'memory' => memory_get_usage(true),
            'project' => [
                'uuid' => config('thenpingme.project_id'),
            ],
            'task' => TaskPayload::make($this->event->task)->toArray(),
        ]);
    }
}
