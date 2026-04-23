<?php

namespace Tests\Redirects\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Eloquent\RedirectModel;
use Statamic\SeoPro\Redirects\Eloquent\RedirectRepository;
use Statamic\SeoPro\Redirects\Redirect;
use Tests\TestCase;

class RedirectRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(RedirectRepository::class);
    }

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
    public function can_find_redirect()
    {
        RedirectModel::create([
            'site' => 'default',
            'source' => '/old-url',
            'destination' => '/new-url',
            'response_code' => 302,
            'enabled' => true,
            'hits' => 0,
            'data' => [],
        ]);

        $redirect = $this->repo->find(1);

        $this->assertInstanceOf(Redirect::class, $redirect);
        $this->assertEquals(1, $redirect->id());
        $this->assertEquals('/old-url', $redirect->source());
        $this->assertEquals('/new-url', $redirect->destination());
        $this->assertEquals(302, $redirect->responseCode());
        $this->assertTrue($redirect->enabled());
    }

    #[Test]
    public function can_save_redirect()
    {
        $redirect = Facades\Redirect::make()
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(302)
            ->enabled(true);

        $this->repo->save($redirect);

        $this->assertNotNull($redirect->id());
        $this->assertDatabaseHas('redirects', [
            'source' => '/old-url',
            'destination' => '/new-url',
            'response_code' => 302,
            'enabled' => true,
        ]);
    }

    #[Test]
    public function can_delete_redirect()
    {
        $redirect = Facades\Redirect::make()
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(302)
            ->enabled(true);

        $this->repo->save($redirect);

        $this->assertDatabaseHas('redirects', ['id' => $redirect->id()]);

        $this->repo->delete($redirect);

        $this->assertDatabaseMissing('redirects', ['id' => $redirect->id()]);
    }

    #[Test]
    public function it_sets_default_site_when_saving_without_one()
    {
        $redirect = Facades\Redirect::make()
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true);

        $this->repo->save($redirect);

        $this->assertDatabaseHas('redirects', [
            'id' => $redirect->id(),
            'site' => 'default',
        ]);
    }
}
