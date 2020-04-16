<?php

namespace Thenpingme\Console\Commands;

use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\TaskPayload;

class ThenpingmeScheduleListCommand extends Command
{
    protected $description = "List your application's scheduled tasks.";

    protected $signature = 'thenpingme:schedule';

    public function handle(): void
    {
        $this->table([
            'Interval',
            'Description',
            'Last Run',
            'Next Due',
        ], $this->schedule());
    }

    protected function schedule(): array
    {
        return collect(Thenpingme::scheduledTasks())
            ->map(function ($task) {
                return TaskPayload::fromTask($task)->toArray();
            })
            ->map(function ($task) {
                return [
                    Thenpingme::translateExpression($task['expression']),
                    $task['description'],
                    CronExpression::factory($task['expression'])->getPreviousRunDate(Carbon::now()),
                    CronExpression::factory($task['expression'])->getNextRunDate(Carbon::now()),
                ];
            })
            ->toArray();
    }
}
