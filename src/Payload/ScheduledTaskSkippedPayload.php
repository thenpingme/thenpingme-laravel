<?php

namespace Thenpingme\Payload;

use Illuminate\Support\Carbon;

class ScheduledTaskSkippedPayload extends ThenpingmePayload
{
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => class_basename($this->event),
            'time' => Carbon::now()->toIso8601String(),
            'project' => [
                'uuid' => config('thenpingme.project_id'),
            ],
            'task' => TaskPayload::make($this->event->task)->toArray(),
        ]);
    }
}
