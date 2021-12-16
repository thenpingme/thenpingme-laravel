<?php

declare(strict_types=1);

namespace Thenpingme\Payload;

use Illuminate\Support\Carbon;

final class ScheduledTaskFinishedPayload extends ThenpingmePayload
{
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => class_basename($this->event),
            'time' => Carbon::now()->toIso8601String(),
            'runtime' => $this->event->runtime,
            'exit_code' => $this->event->task->exitCode,
            'memory' => memory_get_peak_usage(true),
            'task' => TaskPayload::make($this->event->task)->toArray(),
        ]);
    }
}
