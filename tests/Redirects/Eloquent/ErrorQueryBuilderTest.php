<?php

namespace Tests\Redirects\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Facades\Error;
use Statamic\SeoPro\Redirects\Eloquent\ErrorModel;
use Statamic\SeoPro\Redirects\Error as ErrorInstance;
use Tests\TestCase;

class ErrorQueryBuilderTest extends TestCase
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

    #[Test]
    public function can_query_with_where()
    {
        ErrorModel::create([
            'site' => 'default',
            'url' => '/missing-page',
            'hits' => 3,
            'last_hit_at' => '2026-04-21 12:00:00',
            'data' => [],
        ]);

        ErrorModel::create([
            'site' => 'default',
            'url' => '/another-page',
            'hits' => 1,
            'last_hit_at' => '2026-04-20 10:00:00',
            'data' => [],
        ]);

        $results = Error::query()->where('url', '/missing-page')->get();

        $this->assertCount(1, $results);
        $this->assertInstanceOf(ErrorInstance::class, $results->first());
        $this->assertEquals('/missing-page', $results->first()->url());
    }
}
