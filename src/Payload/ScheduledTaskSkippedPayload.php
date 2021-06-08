<?php

declare(strict_types=1);

namespace Thenpingme\Payload;

use Illuminate\Support\Carbon;

final class ScheduledTaskSkippedPayload extends ThenpingmePayload
{
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => class_basename($this->event),
            'time' => Carbon::now()->toIso8601String(),
            'task' => TaskPayload::make($this->event->task)->toArray(),
        ]);
    }
}
