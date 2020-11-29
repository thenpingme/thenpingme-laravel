<?php

namespace Thenpingme\Payload;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;

class ThenpingmeSetupPayload implements Arrayable
{
    /** @var \Thenpingme\Collections\ScheduledTaskCollection */
    private $tasks;

    /** @var string */
    private $signingKey;

    private function __construct(ScheduledTaskCollection $tasks, string $signingKey = null)
    {
        $this->tasks = $tasks;
        $this->signingKey = $signingKey;
    }

    public static function make(ScheduledTaskCollection $tasks, string $signingKey): self
    {
        return new static($tasks, $signingKey);
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
                'signing_key' => $this->signingKey,
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
