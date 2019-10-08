<?php

namespace Thenpingme\Laravel\Tests;

use Illuminate\Support\Collection;

class ThenpingmeSetupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        touch(base_path('.example.env'));
        touch(base_path('.env'));
    }

    public function tearDown(): void
    {
        unlink(base_path('.example.env'));
        unlink(base_path('.env'));
    }

    /** @test */
    public function it_correctly_sets_environment_variables()
    {
        $this->artisan('thenpingme:setup aaa-bbbb-c1c1c1-ddd-ef1');

        $this->assertTrue($this->loadEnv(true)->contains('THENPINGME_PROJECT_ID='.PHP_EOL));
        $this->assertTrue($this->loadEnv(true)->contains('THENPINGME_SIGNING_KEY='.PHP_EOL));
        $this->assertTrue($this->loadEnv(true)->contains('THENPINGME_QUEUE_PING=false'.PHP_EOL));

        $this->assertTrue($this->loadEnv()->contains('THENPINGME_PROJECT_ID=aaa-bbbb-c1c1c1-ddd-ef1'.PHP_EOL));
        $this->assertTrue($this->loadEnv()->contains('THENPINGME_SIGNING_KEY='.PHP_EOL));
        $this->assertTrue($this->loadEnv()->contains('THENPINGME_QUEUE_PING=false'.PHP_EOL));
    }

    protected function loadEnv($example = true)
    {
        return Collection::make(file(base_path($example ? '.example.env' : '.env')));
    }
}
