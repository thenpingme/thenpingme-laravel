<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Thenpingme\Client\Client;
use Thenpingme\Client\TestClient;
use Thenpingme\Facades\Thenpingme;

class ThenpingmeSetupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->app->instance(Client::class, new TestClient);

        touch(base_path('.env.example'));
        touch(base_path('.env'));
    }

    public function tearDown(): void
    {
        unlink(base_path('.env.example'));
        unlink(base_path('.env'));
    }

    /** @test */
    public function it_correctly_sets_environment_variables()
    {
        Thenpingme::shouldReceive('generateSigningKey')->once()->andReturn('this-is-the-signing-secret');
        Thenpingme::shouldReceive('scheduledTasks')->once()->andReturn([]);

        $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

        $this->assertTrue($this->loadEnv(true)->contains('THENPINGME_PROJECT_ID='.PHP_EOL));
        $this->assertTrue($this->loadEnv(true)->contains('THENPINGME_SIGNING_KEY='.PHP_EOL));
        $this->assertTrue($this->loadEnv(true)->contains('THENPINGME_QUEUE_PING=false'.PHP_EOL));

        $this->assertTrue($this->loadEnv()->contains('THENPINGME_PROJECT_ID=aaa-bbbb-c1c1c1-ddd-ef1'.PHP_EOL));
        $this->assertTrue($this->loadEnv()->contains('THENPINGME_SIGNING_KEY=this-is-the-signing-secret'.PHP_EOL));
        $this->assertTrue($this->loadEnv()->contains('THENPINGME_QUEUE_PING=false'.PHP_EOL));
    }

    /** @test */
    public function it_sets_up_initial_scheduled_tasks()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->command('test:command')->hourly();

        Thenpingme::shouldReceive('generateSigningKey')->once();
        Thenpingme::shouldReceive('scheduledTasks')->once();

        $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');
    }

    protected function loadEnv($example = false)
    {
        return Collection::make(file(base_path($example ? '.example.env' : '.env')));
    }
}
