<?php

namespace Thenpingme\Payload;

use Illuminate\Support\Carbon;

class ScheduledTaskFailedPayload extends ThenpingmePayload
{
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => class_basename($this->event),
            'time' => Carbon::now()->toIso8601String(),
            'exception' => $this->event->exception->getMessage(),
            'exit_code' => $this->event->task->exitCode,
            'memory' => memory_get_usage(true),
            'task' => TaskPayload::make($this->event->task)->toArray(),
        ]);
    }
}