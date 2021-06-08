<?php

declare(strict_types=1);

namespace Thenpingme\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Thenpingme\Facades\Thenpingme;

class ThenpingmeVerifyCommand extends Command
{
    protected $description = 'Verify that your configured scheduled tasks are correctly configured.';

    protected $signature = 'thenpingme:verify';

    public function __construct(protected Translator $translator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (($collisions = Thenpingme::scheduledTasks()->collisions())->isNotEmpty()) {
            $this->table(
                ['Type', 'Command', 'Expression', 'Interval', 'Description', 'Extra'],
                $collisions->map(function (array $task): array {
                    return Arr::only($task, ['type', 'command', 'expression', 'interval', 'description', 'extra']);
                })
            );

            $this->error($this->translator->get('thenpingme::translations.indistinguishable_tasks'));

            if ($collisions->hasNonUniqueJobs()) {
                $this->line($this->translator->get('thenpingme::translations.duplicate_jobs'));
            }

            if ($collisions->hasNonUniqueClosures()) {
                $this->line($this->translator->get('thenpingme::translations.duplicate_closures'));
            }

            return 1;
        }

        $this->info($this->translator->get('thenpingme::translations.healthy_tasks'));

        return 0;
    }
}
