<?php

namespace Thenpingme\Payload;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;

class SyncPayload implements Arrayable
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
            'thenpingme' => [
                'version' => Thenpingme::version(),
            ],
            'project' => array_filter([
                'uuid' => Config::get('thenpingme.project_id'),
                'name' => Config::get('app.name'),
                'release' => Config::get('thenpingme.release'),
                'timezone' => Carbon::now()->timezone->toOffsetName(),
            ]),
            'tasks' => array_reduce($this->tasks->toArray(), function ($tasks, $task) {
                $tasks[] = Arr::except(TaskPayload::make($task)->toArray(), ['extra']);

                return $tasks;
            }, []),
        ];
    }
}
