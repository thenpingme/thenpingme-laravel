<?php

namespace Thenpingme\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Translation\Translator;
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
        if (($tasks = Thenpingme::scheduledTasks()->nonUnique())->isNotEmpty()) {
            $this->table(['Type', 'Expression', 'Interval', 'Description', 'Extra'], $tasks->values());

            $this->error($this->translator->get('thenpingme::messages.indistinguishable_tasks'));

            if ($tasks->hasNonUniqueJobs()) {
                $this->line($this->translator->get('thenpingme::messages.duplicate_jobs'));
            }

            if ($tasks->hasNonUniqueClosures()) {
                $this->line($this->translator->get('thenpingme::messages.duplicate_closures'));
            }

            return 1;
        }

        $this->info($this->translator->get('thenpingme::messages.healthy_tasks'));
    }
}
