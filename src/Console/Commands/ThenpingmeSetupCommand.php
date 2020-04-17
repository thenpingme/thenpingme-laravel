<?php

namespace Thenpingme\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use sixlive\DotenvEditor\DotenvEditor;
use Thenpingme\Client\Client;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\ThenpingmeSetupPayload;
use Thenpingme\ThenpingmeServiceProvider;

class ThenpingmeSetupCommand extends Command
{
    protected $description = 'Configure your application to report scheduled tasks to ThenPing.me automatically.';

    protected $signature = 'thenpingme:setup {project_id?  : The UUID of the ThenPing.me project you are setting up}
                                             {--tasks-only : Only send your application tasks to ThenPing.me}';

    /** @var bool */
    protected $envMissing = false;

    /** @var \Illuminate\Console\Scheduling\Schedule */
    protected $schedule;

    /** @var array */
    protected $scheduledTasks;

    /** @var string */
    protected $signingKey;

    /** @var \Illuminate\Contracts\Translation\Translator */
    protected $translator;

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->translator = $translator;
    }

    public function handle(Schedule $schedule)
    {
        $this->schedule = $schedule;

        if (! $this->prepareTasks()) {
            return 1;
        }

        if (! $this->option('tasks-only')) {
            $this->task($this->translator->get('thenpingme::messages.setup.signing_key'), function () {
                return $this->generateSigningKey();
            });

            $this->task($this->translator->get('thenpingme::messages.setup.write_env'), function () {
                return $this->writeEnvFile();
            });

            $this->task($this->translator->get('thenpingme::messages.setup.write_env_example'), function () {
                return $this->writeExampleEnvFile();
            });

            $this->task($this->translator->get('thenpingme::messages.setup.publish_config'), function () {
                return $this->publishConfig();
            });
        }

        $this->task(
            $this->translator->get('thenpingme::messages.initial_setup', [
                'url' => parse_url(config('thenpingme.api_url'), PHP_URL_HOST),
            ]),
            function () {
                return $this->setupInitialTasks();
            }
        );

        if ($this->envMissing) {
            $this->error($this->translator->get('thenpingme::messages.env_missing'));
            $this->info('    php artisan thenpingme:setup --tasks-only');
            $this->line(sprintf('THENPINGME_PROJECT_ID=%s', $this->argument('project_id')));
            $this->line(sprintf('THENPINGME_SIGNING_KEY=%s', $this->signingKey));

            return 1;
        }
    }

    protected function prepareTasks(): bool
    {
        $this->scheduledTasks = Thenpingme::scheduledTasks();

        if (($nonUnique = $this->scheduledTasks->nonUnique())->isNotEmpty()) {
            $this->table(
                ['Type', 'Expression', 'Interval', 'Description', 'Extra'],
                $nonUnique->map(function ($task) {
                    return Arr::only($task, ['type', 'expression', 'interval', 'description', 'extra']);
                })
            );

            $this->error($this->translator->get('thenpingme::messages.indistinguishable_tasks'));

            return false;
        }

        return true;
    }

    protected function writeEnvFile(): bool
    {
        try {
            tap(new DotenvEditor, function ($editor) {
                $editor->load(base_path('.env'));
                $editor->set('THENPINGME_PROJECT_ID', $this->argument('project_id'));
                $editor->set('THENPINGME_SIGNING_KEY', $this->signingKey);
                $editor->set('THENPINGME_QUEUE_PING', 'true');
                $editor->save();
            });

            Config::set([
                'thenpingme.project_id' => $this->argument('project_id'),
                'thenpingme.signing_key' => $this->signingKey,
            ]);

            if (file_exists(app()->getCachedConfigPath())) {
                Artisan::call('config:cache');
            }

            return true;
        } catch (InvalidArgumentException $e) {
            $this->envMissing = true;

            return false;
        }
    }

    protected function writeExampleEnvFile(): bool
    {
        try {
            tap(new DotenvEditor, function ($editor) {
                $editor->load(base_path('.env.example'));
                $editor->set('THENPINGME_PROJECT_ID', '');
                $editor->set('THENPINGME_SIGNING_KEY', '');
                $editor->set('THENPINGME_QUEUE_PING', 'true');
                $editor->save();
            });

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    protected function generateSigningKey(): bool
    {
        $this->signingKey = Thenpingme::generateSigningKey();

        return true;
    }

    protected function publishConfig(): bool
    {
        if (! file_exists(config_path('thenpingme.php'))) {
            return $this->call('vendor:publish', [
                '--provider' => ThenpingmeServiceProvider::class,
            ]) === 0;
        }

        return true;
    }

    protected function setupInitialTasks(): bool
    {
        if ($this->envMissing) {
            return false;
        }

        app(Client::class)
            ->setup()
            ->useSecret($this->option('tasks-only') ? config('thenpingme.project_id') : $this->argument('project_id'))
            ->payload(
                ThenpingmeSetupPayload::make(Thenpingme::scheduledTasks())->toArray()
            )
            ->dispatch();

        return true;
    }
}
