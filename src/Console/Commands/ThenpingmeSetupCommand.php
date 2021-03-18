<?php

namespace Thenpingme\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use sixlive\DotenvEditor\DotenvEditor;
use Thenpingme\Client\Client;
use Thenpingme\Console\Commands\Concerns\FetchesTasks;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\Payload\ThenpingmeSetupPayload;
use Thenpingme\ThenpingmeServiceProvider;

class ThenpingmeSetupCommand extends Command
{
    use FetchesTasks;

    protected $description = 'Configure your application to report scheduled tasks to thenping.me automatically.';

    protected $signature = 'thenpingme:setup {project_id?  : The UUID of the thenping.me project you are setting up}
                                             {--tasks-only : Only send your application tasks to thenping.me}';

    /** @var \Illuminate\Console\Scheduling\Schedule */
    protected $schedule;

    /** @var \Thenpingme\Collections\ScheduledTaskCollection */
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

        if (! $this->canBeSetup()) {
            $this->error($this->translator->get('thenpingme::translations.env_missing'));
            $this->info('    php artisan thenpingme:setup --tasks-only');
            $this->line(sprintf('THENPINGME_PROJECT_ID=%s', $this->argument('project_id')));

            return 1;
        }

        if (! $this->prepareTasks()) {
            return 1;
        }

        $this->task($this->translator->get('thenpingme::translations.setup.signing_key'), function () {
            return $this->generateSigningKey();
        });

        if (! $this->option('tasks-only')) {
            $this->task($this->translator->get('thenpingme::translations.setup.write_env'), function () {
                return $this->writeEnvFile();
            });

            $this->task($this->translator->get('thenpingme::translations.setup.write_env_example'), function () {
                return $this->writeExampleEnvFile();
            });

            $this->task($this->translator->get('thenpingme::translations.setup.publish_config'), function () {
                return $this->publishConfig();
            });
        }

        $this->task(
            $this->translator->get('thenpingme::translations.initial_setup', [
                'url' => parse_url(Config::get('thenpingme.api_url'), PHP_URL_HOST),
            ]),
            function () {
                return $this->setupInitialTasks();
            }
        );

        if (! $this->envExists()) {
            $this->error($this->translator->get('thenpingme::translations.signing_key_environment'));
            $this->line(sprintf('THENPINGME_SIGNING_KEY=%s', $this->signingKey));
        }
    }

    protected function canBeSetup(): bool
    {
        if ($this->option('tasks-only') && Config::get('thenpingme.project_id')) {
            return true;
        }

        return $this->envExists();
    }

    protected function envExists(): bool
    {
        try {
            tap(new DotenvEditor)->load(base_path('.env'));

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    protected function writeEnvFile(): bool
    {
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
        config(['thenpingme.queue_ping' => false]);

        app(Client::class)
            ->setup()
            ->useSecret($this->option('tasks-only') ? Config::get('thenpingme.project_id') : $this->argument('project_id'))
            ->payload(
                ThenpingmeSetupPayload::make(
                    Thenpingme::scheduledTasks(),
                    Config::get('thenpingme.signing_key') ?: $this->signingKey
                )->toArray()
            )
            ->dispatch();

        return true;
    }
}
