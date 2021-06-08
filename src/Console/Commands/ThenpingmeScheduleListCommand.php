<?php

declare(strict_types=1);

namespace Thenpingme\Console\Commands;

use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Carbon;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\TaskPayload;

class ThenpingmeScheduleListCommand extends Command
{
    protected $description = "List your application's scheduled tasks.";

    protected $signature = 'thenpingme:schedule';

    protected Translator $translator;

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

    protected function schedule(): ScheduledTaskCollection
    {
        $collisions = Thenpingme::scheduledTasks()->collisions()->pluck('mutex')->unique();

        return ScheduledTaskCollection::make(
            Thenpingme::scheduledTasks()
                ->map(function (Event $task): array {
                    return TaskPayload::fromTask($task)->toArray();
                })
                ->map(function (array $task) use ($collisions): array {
                    return [
                        $collisions->contains($task['mutex']) ? '<error> ! </error>' : '',
                        $command = $task['command'] ?: $task['description'],
                        Thenpingme::translateExpression($task['expression']),
                        $task['description'] !== $command ? $task['description'] : null,
                        (new CronExpression($task['expression']))->getPreviousRunDate(Carbon::now()),
                        (new CronExpression($task['expression']))->getNextRunDate(Carbon::now()),
                    ];
                })
        );
    }
}
