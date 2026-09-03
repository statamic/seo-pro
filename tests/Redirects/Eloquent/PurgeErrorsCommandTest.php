<?php

namespace Tests\Redirects\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Redirects\Eloquent\ErrorModel;
use Tests\TestCase;

class PurgeErrorsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.seo-pro.redirects.errors.driver', 'database');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../src/Commands/stubs');
    }

    /**
     * @see https://github.com/statamic/seo-pro/issues/647
     */
    #[Test]
    public function it_purges_errors_without_a_last_hit_at()
    {
        Carbon::setTestNow('2026-04-21 12:00:00');

        ErrorModel::create(['site' => 'default', 'url' => '/never-hit', 'hits' => 1, 'last_hit_at' => null, 'data' => []]);
        ErrorModel::create(['site' => 'default', 'url' => '/recent', 'hits' => 1, 'last_hit_at' => '2026-04-20 12:00:00', 'data' => []]);

        $this
            ->artisan('statamic:seo-pro:purge-errors')
            ->assertSuccessful();

        $this->assertDatabaseMissing('seo_pro_errors', ['url' => '/never-hit']);
        $this->assertDatabaseHas('seo_pro_errors', ['url' => '/recent']);
    }

    #[Test]
    public function it_purges_the_least_valuable_errors_when_max_errors_is_exceeded()
    {
        Carbon::setTestNow('2026-04-21 12:00:00');

        config(['statamic.seo-pro.redirects.errors.max_errors' => 2]);

        ErrorModel::create(['site' => 'default', 'url' => '/never-hit', 'hits' => 6, 'last_hit_at' => null, 'data' => []]);
        ErrorModel::create(['site' => 'default', 'url' => '/rarely-hit', 'hits' => 1, 'last_hit_at' => '2026-04-20 12:00:00', 'data' => []]);
        ErrorModel::create(['site' => 'default', 'url' => '/stale-popular', 'hits' => 5, 'last_hit_at' => '2026-04-01 12:00:00', 'data' => []]);
        ErrorModel::create(['site' => 'default', 'url' => '/fresh-popular', 'hits' => 5, 'last_hit_at' => '2026-04-20 12:00:00', 'data' => []]);
        ErrorModel::create(['site' => 'default', 'url' => '/most-popular', 'hits' => 9, 'last_hit_at' => '2026-04-10 12:00:00', 'data' => []]);

        $this
            ->artisan('statamic:seo-pro:purge-errors')
            ->assertSuccessful();

        $this->assertDatabaseMissing('seo_pro_errors', ['url' => '/never-hit']);
        $this->assertDatabaseMissing('seo_pro_errors', ['url' => '/rarely-hit']);
        $this->assertDatabaseMissing('seo_pro_errors', ['url' => '/stale-popular']);
        $this->assertDatabaseHas('seo_pro_errors', ['url' => '/fresh-popular']);
        $this->assertDatabaseHas('seo_pro_errors', ['url' => '/most-popular']);
    }
}
