<?php

declare(strict_types=1);

namespace Thenpingme;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Foundation\Application;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Thenpingme\Client\Client;
use Thenpingme\Client\ThenpingmeClient;
use Thenpingme\Console\Commands\ThenpingmeScheduleListCommand;
use Thenpingme\Console\Commands\ThenpingmeSetupCommand;
use Thenpingme\Console\Commands\ThenpingmeSyncCommand;
use Thenpingme\Console\Commands\ThenpingmeVerifyCommand;
use Thenpingme\Scheduling\Event as ThenpingmeEvent;
use Thenpingme\Signer\Signer;
use Thenpingme\Signer\ThenpingmeSigner;

class ThenpingmeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-thenpingme')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommands([
                ThenpingmeSetupCommand::class,
                ThenpingmeScheduleListCommand::class,
                ThenpingmeVerifyCommand::class,
                ThenpingmeSyncCommand::class,
            ]);
    }

    public function bootingPackage(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->make('events')->subscribe(ScheduledTaskSubscriber::class);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('thenpingme', function () {
            return new Thenpingme;
        });

        $this->app->singleton(Signer::class, function (Application $app): ThenpingmeSigner {
            return $app->make(ThenpingmeSigner::class);
        });

        $this->app->singleton(Client::class, function (Application $app): Client {
            return $app->make(ThenpingmeClient::class);
        });

        Event::mixin(new ThenpingmeEvent);
    }
}
