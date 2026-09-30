<?php

namespace Tests\Redirects\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Eloquent\ErrorModel;
use Statamic\SeoPro\Redirects\Eloquent\ErrorRepository;
use Statamic\SeoPro\Redirects\Error;
use Tests\TestCase;

class ErrorRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(ErrorRepository::class);
    }

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
    public function can_find_error()
    {
        ErrorModel::create([
            'site' => 'default',
            'url' => '/missing-page',
            'hits' => 5,
            'last_hit_at' => '2026-04-21 12:00:00',
            'data' => [],
        ]);

        $error = $this->repo->find(1);

        $this->assertInstanceOf(Error::class, $error);
        $this->assertEquals(1, $error->id());
        $this->assertEquals('/missing-page', $error->url());
        $this->assertEquals(5, $error->hits());
    }

    #[Test]
    public function can_save_error()
    {
        $error = Facades\Error::make()
            ->url('/missing-page')
            ->hits(3)
            ->lastHitAt('2026-04-21 12:00:00');

        $this->repo->save($error);

        $this->assertNotNull($error->id());
        $this->assertDatabaseHas('seo_pro_errors', [
            'url' => '/missing-page',
            'hits' => 3,
        ]);
    }

    #[Test]
    public function can_delete_error()
    {
        $error = Facades\Error::make()
            ->url('/missing-page')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00');

        $this->repo->save($error);

        $this->assertDatabaseHas('seo_pro_errors', ['id' => $error->id()]);

        $this->repo->delete($error);

        $this->assertDatabaseMissing('seo_pro_errors', ['id' => $error->id()]);
    }

    #[Test]
    public function it_does_not_set_a_null_id_when_saving_a_new_error()
    {
        $error = Facades\Error::make()
            ->url('/missing-page')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00');

        $method = new \ReflectionMethod($this->repo, 'toModel');
        $model = $method->invoke($this->repo, $error);

        $this->assertArrayNotHasKey('id', $model->getAttributes());
    }

    #[Test]
    public function it_sets_default_site_when_saving_without_one()
    {
        $error = Facades\Error::make()
            ->url('/missing-page')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00');

        $this->repo->save($error);

        $this->assertDatabaseHas('seo_pro_errors', [
            'id' => $error->id(),
            'site' => 'default',
        ]);
    }
}
