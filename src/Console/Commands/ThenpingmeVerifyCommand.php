<?php

namespace Thenpingme\Console\Commands;

use Illuminate\Console\Command;
use Thenpingme\Facades\Thenpingme;

class ThenpingmeVerifyCommand extends Command
{
    protected $description = 'Verify that your configured scheduled tasks are correctly configured.';

    protected $signature = 'thenpingme:verify';

    public function handle()
    {
        if (($tasks = Thenpingme::scheduledTasks()->nonUnique())->isNotEmpty()) {
            $this->table(['Type', 'Expression', 'Interval', 'Description', 'Extra'], $tasks->values());

            $this->error('Tasks have been identified that are not uniquely distinguishable!');

            if ($tasks->hasNonUniqueJobs()) {
                $this->line('Job-based tasks should set a description, or run on a unique schedule.');
            }

            if ($tasks->hasNonUniqueClosures()) {
                $this->line('Closure-based tasks should set a description to ensure uniqueness.');
            }

            return 1;
        }

        $this->info('Your tasks are correctly configured and can be synced to thenping.me!');
    }
}
