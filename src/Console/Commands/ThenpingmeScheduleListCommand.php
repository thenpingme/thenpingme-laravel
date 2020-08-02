<?php

namespace Thenpingme\Console\Commands;

use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Carbon;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\TaskPayload;

class ThenpingmeScheduleListCommand extends Command
{
    protected $description = "List your application's scheduled tasks.";

    protected $signature = 'thenpingme:schedule';

    /** @var \Illuminate\Contracts\Translation\Translator */
    protected $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;

        parent::__construct();
    }

    public function handle()
    {
        $this->table([
            '',
            'Command',
            'Interval',
            'Description',
            'Last Run',
            'Next Due',
        ], $this->schedule());

        if (Thenpingme::scheduledTasks()->collisions()->isNotEmpty()) {
            $this->error($this->translator->get('thenpingme::messages.indistinguishable_tasks'));

            return 1;
        }
    }

    /**
     * @return \Thenpingme\Collections\ScheduledTaskCollection
     */
    protected function schedule()
    {
        $collisions = Thenpingme::scheduledTasks()->collisions()->pluck('mutex')->unique();

        return Thenpingme::scheduledTasks()
            ->map(function ($task) {
                return TaskPayload::fromTask($task)->toArray();
            })
            ->map(function ($task) use ($collisions) {
                return [
                    $collisions->contains($task['mutex']) ? '<error> ! </error>' : '',
                    $command = $task['command'] ?: $task['description'],
                    Thenpingme::translateExpression($task['expression']),
                    $task['description'] !== $command ? $task['description'] : null,
                    CronExpression::factory($task['expression'])->getPreviousRunDate(Carbon::now()),
                    CronExpression::factory($task['expression'])->getNextRunDate(Carbon::now()),
                ];
            });
    }
}
