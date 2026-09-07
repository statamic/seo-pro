<?php

namespace Tests\Redirects\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Facades\Redirect;
use Statamic\SeoPro\Redirects\Eloquent\RedirectModel;
use Statamic\SeoPro\Redirects\Redirect as RedirectInstance;
use Tests\TestCase;

class RedirectQueryBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.seo-pro.redirects.driver', 'database');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../src/Commands/stubs');
    }

    #[Test]
    public function can_query_with_where()
    {
        RedirectModel::create([
            'site' => 'default',
            'source' => '/old-url',
            'destination' => '/new-url',
            'response_code' => 301,
            'enabled' => true,
            'hits' => 0,
            'data' => [],
        ]);

        RedirectModel::create([
            'site' => 'default',
            'source' => '/another-url',
            'destination' => '/another-new-url',
            'response_code' => 302,
            'enabled' => true,
            'hits' => 0,
            'data' => [],
        ]);

        $results = Redirect::query()->where('source', '/old-url')->get();

        $this->assertCount(1, $results);
        $this->assertInstanceOf(RedirectInstance::class, $results->first());
        $this->assertEquals('/old-url', $results->first()->source());
    }
}
