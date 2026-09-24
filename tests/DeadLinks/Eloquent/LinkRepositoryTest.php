<?php

namespace Tests\DeadLinks\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\DeadLinks\Eloquent\LinkModel;
use Statamic\SeoPro\DeadLinks\Eloquent\LinkRepository;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\Facades\DeadLink;
use Tests\TestCase;

class LinkRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(LinkRepository::class);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.seo-pro.dead_links.driver', 'database');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../src/Commands/stubs');
    }

    #[Test]
    public function can_find_link()
    {
        LinkModel::create([
            'site' => 'default',
            'url' => 'https://example.com/broken',
            'status' => 'failing',
            'status_code' => 404,
            'consecutive_failures' => 3,
            'references' => [],
            'data' => [],
        ]);

        $link = $this->repo->find(1);

        $this->assertInstanceOf(Link::class, $link);
        $this->assertEquals('https://example.com/broken', $link->url());
        $this->assertEquals('failing', $link->status());
        $this->assertEquals(404, $link->statusCode());
        $this->assertEquals(3, $link->consecutiveFailures());
    }

    #[Test]
    public function can_save_link()
    {
        $link = DeadLink::make()
            ->url('https://example.com/broken')
            ->status(Link::STATUS_FAILING)
            ->statusCode(500);

        $this->repo->save($link);

        $this->assertDatabaseHas('seo_pro_dead_links', [
            'url' => 'https://example.com/broken',
            'status' => 'failing',
            'status_code' => 500,
        ]);

        $this->assertNotNull($link->id());
    }

    #[Test]
    public function can_save_and_retrieve_references()
    {
        $link = DeadLink::make()
            ->url('https://example.com/broken')
            ->references([
                ['subject_type' => 'entry', 'subject_id' => '1', 'site' => 'en', 'field_path' => 'body', 'title' => 'Home', 'edit_url' => '/cp/x'],
            ]);

        $this->repo->save($link);

        $fresh = $this->repo->find($link->id());

        $this->assertCount(1, $fresh->references());
        $this->assertEquals('entry', $fresh->references()->first()['subject_type']);
    }

    #[Test]
    public function can_delete_link()
    {
        $link = DeadLink::make()->url('https://example.com/broken');
        $this->repo->save($link);

        $this->repo->delete($link);

        $this->assertDatabaseMissing('seo_pro_dead_links', ['url' => 'https://example.com/broken']);
    }

    #[Test]
    public function saving_an_existing_link_updates_it_rather_than_duplicating()
    {
        $link = DeadLink::make()->url('https://example.com/broken')->status(Link::STATUS_OK);
        $this->repo->save($link);

        $link->status(Link::STATUS_FAILING);
        $this->repo->save($link);

        $this->assertEquals(1, LinkModel::count());
        $this->assertEquals('failing', LinkModel::first()->status);
    }
}
