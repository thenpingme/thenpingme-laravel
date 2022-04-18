<?php

namespace Thenpingme\Scheduling;

class Event
{
    public array $thenpingmeOptions = [];

    public function thenpingme(): callable
    {
        return function (
            ?int $grace_period = null,
            ?int $allowed_run_time = null,
            ?int $notify_after_consecutive_alerts = null,
            ?bool $skip = false,
            ?int $output = null
        ) {
            $this->thenpingmeOptions = [
                'grace_period' => $grace_period,
                'allowed_run_time' => $allowed_run_time,
                'notify_after_consecutive_alerts' => $notify_after_consecutive_alerts,
                'skip' => $skip,
                'output' => $output,
            ];

            // If output is configured to any non-null value we need to ensure that
            // output is being captured irrespective of the selected level as we
            // don't know the status of the task until execution is finished.
            if (! is_null($output)) {
                /* @phpstan-ignore-next-line */
                $this->ensureOutputIsBeingCaptured();
            }

            return $this;
        };
    }
}
