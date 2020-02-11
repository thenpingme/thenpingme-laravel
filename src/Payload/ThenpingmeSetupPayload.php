<?php

namespace Thenpingme\Payload;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Config;

class ThenpingmeSetupPayload implements Arrayable
{
    private $tasks;

    private function __construct(array $tasks)
    {
        $this->tasks = $tasks;
    }

    public static function make(array $tasks): self
    {
        return new static($tasks);
    }

    public function toArray(): array
    {
        return [
            'project' => [
                'uuid' => Config::get('thenpingme.project_id'),
                'name' => Config::get('app.name'),
                'signing_key' => Config::get('thenpingme.signing_key'),
                'timezone' => config('app.timezone'),
            ],
            'tasks' => array_reduce($this->tasks, function ($tasks, $task) {
                $tasks[] = TaskPayload::make($task)->toArray();

                return $tasks;
            }, []),
        ];
    }
}
