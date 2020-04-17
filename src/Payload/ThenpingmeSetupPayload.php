<?php

namespace Thenpingme\Payload;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Config;
use Thenpingme\Collections\ScheduledTaskCollection;

class ThenpingmeSetupPayload implements Arrayable
{
    /** @var \Thenpingme\Collections\ScheduledTaskCollection */
    private $tasks;

    private function __construct(ScheduledTaskCollection $tasks)
    {
        $this->tasks = $tasks;
    }

    public static function make(ScheduledTaskCollection $tasks): self
    {
        return new static($tasks);
    }

    public function toArray(): array
    {
        return [
            'project' => array_filter([
                'uuid' => Config::get('thenpingme.project_id'),
                'name' => Config::get('app.name'),
                'signing_key' => Config::get('thenpingme.signing_key'),
                'release' => Config::get('thenpingme.release'),
            ]),
            'tasks' => array_reduce($this->tasks->toArray(), function ($tasks, $task) {
                $tasks[] = TaskPayload::make($task)->toArray();

                return $tasks;
            }, []),
        ];
    }
}
