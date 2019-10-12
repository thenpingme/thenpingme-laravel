<?php

namespace Thenpingme\Payload;

use Illuminate\Support\Carbon;

class ScheduledTaskFinishedPayload extends ThenpingmePayload
{
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'type' => 'finished',
            'time' => Carbon::now()->toIso8601String(),
            'runtime' => sprintf('%.2fs', $this->event->runtime),
            'exit_code' => $this->event->task->exitCode,
        ]);
    }
}
