<?php

declare(strict_types=1);

namespace Thenpingme\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Config;
use Thenpingme\Client\Client;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Console\Commands\Concerns\FetchesTasks;
use Thenpingme\Payload\SyncPayload;

class ThenpingmeSyncCommand extends Command
{
    use FetchesTasks;

    protected $description = "Sync your application's scheduled tasks with thenping.me";

    protected $signature = 'thenpingme:sync';

    protected ?ScheduledTaskCollection $scheduledTasks = null;

    protected Translator $translator;

    public function __construct(Translator $translator)
    {
        parent::__construct();

        $this->translator = $translator;
    }

    public function handle(): int
    {
        if (! $this->prepareTasks()) {
            return 1;
        }

        $this->task($this->translator->get('thenpingme::translations.syncing_tasks', [
            'url' => parse_url(Config::get('thenpingme.api_url'), PHP_URL_HOST),
        ]), function () {
            return $this->syncTasks();
        });

        $this->info($this->translator->get('thenpingme::translations.successful_sync'));

        return 0;
    }

    protected function syncTasks(): bool
    {
        config(['thenpingme.queue_ping' => false]);

        app(Client::class)
            ->sync()
            ->payload(SyncPayload::make($this->scheduledTasks)->toArray())
            ->dispatch();

        return true;
    }
}
