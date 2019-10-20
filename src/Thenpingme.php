<?php

namespace Thenpingme;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Thenpingme
{
    public function generateSigningKey(): string
    {
        return Str::random(512);
    }

    public function scheduledTasks(): array
    {
        return with(app(Schedule::class), function ($scheduler) {
            return collect($scheduler->events())
                ->filter(function ($event) {
                    return App::environment($event->environments)
                        || empty($event->environments);
                })
                ->toArray();
        });
    }
}
