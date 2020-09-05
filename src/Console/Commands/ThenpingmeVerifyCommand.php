<?php

namespace Thenpingme\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Thenpingme\Facades\Thenpingme;

class ThenpingmeVerifyCommand extends Command
{
    protected $description = 'Verify that your configured scheduled tasks are correctly configured.';

    protected $signature = 'thenpingme:verify';

    /** @var \Illuminate\Contracts\Translation\Translator */
    protected $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;

        parent::__construct();
    }

    public function handle()
    {
        if (($collisions = Thenpingme::scheduledTasks()->collisions())->isNotEmpty()) {
            $this->table(
                ['Type', 'Command', 'Expression', 'Interval', 'Description', 'Extra'],
                $collisions->map(function ($task) {
                    return Arr::only($task, ['type', 'command', 'expression', 'interval', 'description', 'extra']);
                })
            );

            $this->error($this->translator->get('thenpingme::messages.indistinguishable_tasks'));

            if ($collisions->hasNonUniqueJobs()) {
                $this->line($this->translator->get('thenpingme::messages.duplicate_jobs'));
            }

            if ($collisions->hasNonUniqueClosures()) {
                $this->line($this->translator->get('thenpingme::messages.duplicate_closures'));
            }

            return 1;
        }

        $this->info($this->translator->get('thenpingme::messages.healthy_tasks'));
    }
}
