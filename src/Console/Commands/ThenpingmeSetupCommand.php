<?php

namespace Thenpingme\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Thenpingme\Facades\Thenpingme;

class ThenpingmeSetupCommand extends Command
{
    protected $signature = 'thenpingme:setup {project_id : The UUID of the ThenPing.me project you are setting up}';

    protected $description = 'Configure your application to report scheduled tasks to ThenPing.me automatically.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->updateEnv();
    }

    protected function updateEnv(): void
    {
        tap(new Filesystem, function ($filesystem) {
            $path = base_path('.example.env');

            $filesystem->append($path, 'THENPINGME_PROJECT_ID='.PHP_EOL);
            $filesystem->append($path, 'THENPINGME_SIGNING_KEY='.PHP_EOL);
            $filesystem->append($path, 'THENPINGME_QUEUE_PING=false'.PHP_EOL);
        });

        tap(new Filesystem, function ($filesystem) {
            $path = base_path('.env');

            $filesystem->append($path, sprintf('THENPINGME_PROJECT_ID=%s%s', $this->argument('project_id'), PHP_EOL));
            $filesystem->append($path, 'THENPINGME_SIGNING_KEY='.Thenpingme::generateSigningKey().PHP_EOL);
            $filesystem->append($path, 'THENPINGME_QUEUE_PING=false'.PHP_EOL);
        });
    }
}
