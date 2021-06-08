<?php

declare(strict_types=1);

namespace Thenpingme\Payload;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Makeable;

final class SyncPayload implements Arrayable
{
    use Makeable;

    private ScheduledTaskCollection $tasks;

    protected function __construct(ScheduledTaskCollection $tasks)
    {
        $this->tasks = $tasks;
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
            'tasks' => array_reduce($this->tasks->toArray(), function (array $tasks, Event $task) {
                $tasks[] = Arr::except(TaskPayload::make($task)->toArray(), ['extra']);

                return $tasks;
            }, []),
        ];
    }
}
