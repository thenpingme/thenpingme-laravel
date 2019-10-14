<?php

namespace Thenpingme\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\ThenpingmeSetupPayload;

class ThenpingmeSetupCommand extends Command
{
    protected $description = 'Configure your application to report scheduled tasks to ThenPing.me automatically.';

    protected $schedule;

    protected $signature = 'thenpingme:setup {project_id : The UUID of the ThenPing.me project you are setting up}';

    protected $thenpingme;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(Schedule $schedule, Thenpingme $thenpignme): void
    {
        $this->schedule = $schedule;
        $this->thenpignme = $thenpignme;

        $this->updateConfig();
        $this->setupInitialTasks();
    }

    protected function updateConfig(): void
    {
        tap(new Filesystem, function ($filesystem) {
            $path = base_path('.example.env');

            $filesystem->append($path, 'THENPINGME_PROJECT_ID='.PHP_EOL);
            $filesystem->append($path, 'THENPINGME_SIGNING_KEY='.PHP_EOL);
            $filesystem->append($path, 'THENPINGME_QUEUE_PING=false'.PHP_EOL);
        });

        tap(new Filesystem, function ($filesystem) {
            $path = base_path('.env');
            $key = Thenpingme::generateSigningKey();

            $filesystem->append($path, sprintf('THENPINGME_PROJECT_ID=%s%s', $this->argument('project_id'), PHP_EOL));
            $filesystem->append($path, 'THENPINGME_SIGNING_KEY='.$key.PHP_EOL);
            $filesystem->append($path, 'THENPINGME_QUEUE_PING=false'.PHP_EOL);

            Config::set([
                'thenpingme.project_id' => $this->argument('project_id'),
                'thenpingme.signing_key' => $key,
            ]);
        });

        if (file_exists(app()->getCachedConfigPath())) {
            Artisan::call('config:cache');
        }
    }

    protected function setupInitialTasks(): void
    {
        Thenpingme::setup()->payload(
            ThenpingmeSetupPayload::make($this->scheduledTasks())->toArray()
        )->dispatch();
    }

    protected function scheduledTasks(): array
    {
        return collect($this->schedule->events())->filter(function ($event) {
            return App::environment($event->environments)
                || empty($event->environments);
        })->toArray();
    }
}
