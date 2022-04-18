<?php

declare(strict_types=1);

namespace Thenpingme\Console\Commands;

use Illuminate\Console\Command;

class ThenpingmeScheduleListCommand extends Command
{
    protected $description = "List your application's scheduled tasks. [Deprecated]";

    protected $signature = 'thenpingme:schedule';

    public function handle(): int
    {
        $this->error('As of Laravel 9.x, this command as been deprecated in favour of `artisan schedule:list`');

        return 1;
    }
}
