<?php

namespace Thenpingme\Payload;

use Illuminate\Support\Carbon;

class ScheduledTaskStartingPayload extends ThenpingmePayload
{
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => class_basename($this->event),
            'time' => Carbon::now()->toIso8601String(),
            'expires' => Carbon::now()->addMinutes($this->event->task->expiresAt)->toIso8601String(),
            'project' => [
                'uuid' => config('thenpingme.project_id'),
            ],
            'memory' => memory_get_usage(true),
            'task' => TaskPayload::make($this->event->task)->toArray(),
        ]);
    }
}
