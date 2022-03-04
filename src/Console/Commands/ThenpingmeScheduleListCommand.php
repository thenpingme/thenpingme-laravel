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

    public function __construct(protected Translator $translator)
    {
        parent::__construct();
    }

    public function handle(): int
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
            $this->error($this->translator->get('thenpingme::translations.indistinguishable_tasks'));

            return 1;
        }

        return 0;
    }

    protected function schedule(): ScheduledTaskCollection
    {
        $collisions = Thenpingme::scheduledTasks()->collisions()->pluck('mutex')->unique();

        return ScheduledTaskCollection::make(
            Thenpingme::scheduledTasks()
                ->map(function (Event $task): array {
                    return TaskPayload::make($task)->toArray();
                })
                ->map(function (array $task) use ($collisions): array {
                    return [
                        $collisions->contains($task['mutex']) ? '<error> ! </error>' : '',
                        $command = $task['command'] ?: $task['description'],
                        Thenpingme::translateExpression($task['expression']),
                        $task['description'] !== $command ? $task['description'] : null,
                        (new CronExpression($task['expression']))->getPreviousRunDate(Carbon::now())->format('Y-m-d H:i:s'),
                        (new CronExpression($task['expression']))->getNextRunDate(Carbon::now())->format('Y-m-d H:i:s'),
                    ];
                })
        );
    }
}
