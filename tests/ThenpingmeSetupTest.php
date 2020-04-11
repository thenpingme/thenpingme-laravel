<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use sixlive\DotenvEditor\DotenvEditor;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\ThenpingmePingJob;

class ThenpingmeSetupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        config(['thenpingme.api_url' => 'http://thenpingme.test/api']);

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

        tap($this->loadEnv(base_path('.env')), function ($editor) {
            $this->assertEquals('aaa-bbbb-c1c1c1-ddd-ef1', $editor->getEnv('THENPINGME_PROJECT_ID'));
            $this->assertEquals('this-is-the-signing-secret', $editor->getEnv('THENPINGME_SIGNING_KEY'));
            $this->assertEquals('true', $editor->getEnv('THENPINGME_QUEUE_PING'));
        });

        tap($this->loadEnv(base_path('.env.example')), function ($editor) {
            $this->assertEquals('', $editor->getEnv('THENPINGME_PROJECT_ID'));
            $this->assertEquals('', $editor->getEnv('THENPINGME_SIGNING_KEY'));
            $this->assertEquals('true', $editor->getEnv('THENPINGME_QUEUE_PING'));
        });
    }

    /** @test */
    public function it_sets_up_initial_scheduled_tasks()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->command('test:command')->hourly();

        $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

        Queue::assertPushed(ThenpingmePingJob::class, function ($job) {
            $this->assertEquals('aaa-bbbb-c1c1c1-ddd-ef1', $job->payload['project']['uuid']);
            $this->assertEquals(Config::get('thenpingme.signing_key'), $job->payload['project']['signing_key']);
            $this->assertEquals(Config::get('app.name'), $job->payload['project']['name']);

            $this->assertEquals('test:command', $job->payload['tasks'][0]['command']);
            $this->assertEquals('0 * * * *', $job->payload['tasks'][0]['expression']);

            return true;
        });
    }

    /** @test */
    public function it_handles_missing_environment_file()
    {
        Queue::fake(ThenpingmePingJob::class);

        unlink(base_path('.env'));

        Thenpingme::shouldReceive('generateSigningKey')->once()->andReturn('this-is-the-signing-secret');
        Thenpingme::shouldReceive('scheduledTasks')->never();

        $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

        Queue::assertNotPushed(ThenpingmePingJob::class);

        touch(base_path('.env'));
    }

    /** @test */
    public function it_runs_setup_with_tasks_only()
    {
        Queue::fake(ThenpingmePingJob::class);

        $schedule = $this->app->make(Schedule::class);
        $schedule->command('test:command')->hourly();

        config([
            'thenpingme.project_id' => 'aaa-bbbb-c1c1c1-ddd-ef1',
        ]);

        $this->artisan('thenpingme:setup --tasks-only');

        Queue::assertPushed(ThenpingmePingJob::class, function ($job) {
            $this->assertEquals('aaa-bbbb-c1c1c1-ddd-ef1', $job->payload['project']['uuid']);
            $this->assertEquals(Config::get('thenpingme.signing_key'), $job->payload['project']['signing_key']);
            $this->assertEquals(Config::get('app.name'), $job->payload['project']['name']);

            $this->assertEquals('test:command', $job->payload['tasks'][0]['command']);
            $this->assertEquals('0 * * * *', $job->payload['tasks'][0]['expression']);

            return true;
        });
    }

    protected function loadEnv($file)
    {
        return tap(new DotenvEditor)->load($file);
    }
}
