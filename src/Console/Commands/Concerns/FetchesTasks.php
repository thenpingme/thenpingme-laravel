<?php

namespace Thenpingme\Console\Commands\Concerns;

use Illuminate\Support\Arr;
use Thenpingme\Facades\Thenpingme;

trait FetchesTasks
{
    protected function prepareTasks(): bool
    {
        $this->scheduledTasks = Thenpingme::scheduledTasks();

        if (($collisions = $this->scheduledTasks->collisions())->isNotEmpty()) {
            $this->table(
                ['Type', 'Expression', 'Interval', 'Description', 'Extra'],
                $collisions->map(function ($task) {
                    return Arr::only($task, ['type', 'expression', 'interval', 'description', 'extra']);
                })
            );

            $this->error($this->translator->get('thenpingme::translations.indistinguishable_tasks'));

            return false;
        }

        return true;
    }
}
