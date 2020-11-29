<?php

namespace Thenpingme\Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use sixlive\DotenvEditor\DotenvEditor;
use Thenpingme\Collections\ScheduledTaskCollection;
use Thenpingme\Facades\Thenpingme;
use Thenpingme\ThenpingmePingJob;

class ThenpingmeSetupTest extends TestCase
{
    /** @var \Illuminate\Contracts\Translation\Translator */
    protected $translator;

    public function setUp(): void
    {
        parent::setUp();

        Bus::fake();

        $this->translator = $this->app->make(Translator::class);

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
        Thenpingme::shouldReceive('scheduledTasks')->andReturn(new ScheduledTaskCollection);
        Thenpingme::shouldReceive('version');

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
        config(['thenpingme.queue_ping' => true]);

        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->command('test:command')->hourly();
        });

        $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

        Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
            $this->assertEquals('aaa-bbbb-c1c1c1-ddd-ef1', $job->payload['project']['uuid']);
            $this->assertEquals(Config::get('thenpingme.signing_key'), $job->payload['project']['signing_key']);
            $this->assertEquals(Config::get('app.name'), $job->payload['project']['name']);

            $this->assertEquals('test:command', $job->payload['tasks'][0]['command']);
            $this->assertEquals('0 * * * *', $job->payload['tasks'][0]['expression']);

            return true;
        });

        $this->assertFalse(config('thenpingme.queue_ping'));
    }

    /** @test */
    public function it_handles_missing_environment_file()
    {
        unlink(base_path('.env'));

        Thenpingme::shouldReceive('generateSigningKey')->never();
        Thenpingme::shouldReceive('scheduledTasks')->never();

        $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1')
            ->expectsOutput('THENPINGME_PROJECT_ID=aaa-bbbb-c1c1c1-ddd-ef1')
            ->assertExitCode(1);

        Bus::assertNotDispatched(ThenpingmePingJob::class);

        touch(base_path('.env'));
    }

    /** @test */
    public function it_runs_setup_with_tasks_only()
    {
        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->command('test:command')->hourly();
        });

        config(['thenpingme.project_id' => 'aaa-bbbb-c1c1c1-ddd-ef1']);

        $this->artisan('thenpingme:setup --tasks-only');

        Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
            $this->assertEquals('aaa-bbbb-c1c1c1-ddd-ef1', $job->payload['project']['uuid']);
            $this->assertEquals(Config::get('thenpingme.signing_key'), $job->payload['project']['signing_key']);
            $this->assertEquals(Config::get('app.name'), $job->payload['project']['name']);

            $this->assertEquals('test:command', $job->payload['tasks'][0]['command']);
            $this->assertEquals('0 * * * *', $job->payload['tasks'][0]['expression']);

            return true;
        });
    }

    /** @test */
    public function it_runs_setup_with_tasks_only_when_env_does_not_exist()
    {
        unlink(base_path('.env'));

        Thenpingme::shouldReceive('generateSigningKey')->once()->andReturn('secret');
        Thenpingme::shouldReceive('scheduledTasks')->andReturn(new ScheduledTaskCollection);
        Thenpingme::shouldReceive('version');

        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->command('test:command')->hourly();
        });

        config(['thenpingme.project_id' => 'aaa-bbbb-c1c1c1-ddd-ef1']);
        config()->offsetUnset('thenpingme.signing_key');

        $this->artisan('thenpingme:setup --tasks-only')
            ->expectsOutput($this->translator->get('thenpingme::messages.signing_key_environment'))
            ->expectsOutput('THENPINGME_SIGNING_KEY=secret');

        Bus::assertDispatched(ThenpingmePingJob::class, function ($job) {
            $this->assertEquals('aaa-bbbb-c1c1c1-ddd-ef1', $job->payload['project']['uuid']);
            $this->assertEquals('secret', $job->payload['project']['signing_key']);
            $this->assertEquals(Config::get('app.name'), $job->payload['project']['name']);

            return true;
        });

        touch(base_path('.env'));
    }

    /** @test */
    public function it_exits_if_duplicate_tasks_are_detected()
    {
        tap($this->app->make(Schedule::class), function ($schedule) {
            $schedule->job(SomeJob::class)->everyMinute();
            $schedule->job(SomeJob::class)->everyMinute();
        });

        $this->artisan('thenpingme:setup')->assertExitCode(1);

        Bus::assertNotDispatched(ThenpingmePingJob::class);
    }

    protected function loadEnv($file)
    {
        return tap(new DotenvEditor)->load($file);
    }
}
