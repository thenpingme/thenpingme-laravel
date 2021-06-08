<?php

declare(strict_types=1);

namespace Thenpingme\Payload;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Makeable;

final class ThenpingmeSetupPayload implements Arrayable
{
    use Makeable;

    protected function __construct(private ScheduledTaskCollection $tasks, private ?string $signingKey = null)
    {
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
