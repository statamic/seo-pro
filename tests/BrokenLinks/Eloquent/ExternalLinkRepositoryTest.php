<?php

namespace Tests\BrokenLinks\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\BrokenLinks\Eloquent\ExternalLinkModel;
use Statamic\SeoPro\BrokenLinks\Eloquent\ExternalLinkRepository;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\BrokenLinks\Reference;
use Statamic\SeoPro\Facades;
use Tests\TestCase;

class ExternalLinkRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(ExternalLinkRepository::class);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.seo-pro.broken_links.driver', 'database');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../src/Commands/stubs');
    }

    #[Test]
    public function can_find_link()
    {
        ExternalLinkModel::create([
            'site' => 'default',
            'url' => 'https://example.com/broken',
            'status_code' => 404,
            'broken_since' => '2026-09-01 12:00:00',
            'references' => [],
        ]);

        $link = $this->repo->find(1);

        $this->assertInstanceOf(ExternalLink::class, $link);
        $this->assertEquals('https://example.com/broken', $link->url());
        $this->assertNotNull($link->brokenSince());
        $this->assertEquals(404, $link->statusCode());
        $this->assertEquals('2026-09-01 12:00:00', $link->brokenSince()->toDateTimeString());
    }

    #[Test]
    public function can_find_link_by_url()
    {
        $link = Facades\ExternalLink::make()->url('https://example.com/broken');

        $this->repo->save($link);

        $this->assertEquals($link->id(), $this->repo->findByUrl('https://example.com/broken', 'default')->id());
        $this->assertNull($this->repo->findByUrl('https://example.com/broken', 'fr'));
        $this->assertNull($this->repo->findByUrl('https://example.com/unknown', 'default'));
    }

    #[Test]
    public function can_save_link()
    {
        $link = Facades\ExternalLink::make()
            ->url('https://example.com/broken')
            ->brokenSince(now())
            ->statusCode(500);

        $this->repo->save($link);

        $this->assertDatabaseHas('seo_pro_external_links', [
            'url' => 'https://example.com/broken',
            'status_code' => 500,
        ]);

        $this->assertNotNull($link->id());
    }

    #[Test]
    public function can_save_link_with_url_longer_than_255_characters()
    {
        $url = 'https://example.com/'.str_repeat('a', 300);

        $link = Facades\ExternalLink::make()->url($url);

        $this->repo->save($link);

        $this->assertEquals($url, $this->repo->find($link->id())->url());
        $this->assertEquals(hash('sha256', $url), ExternalLinkModel::find($link->id())->url_hash);
    }

    #[Test]
    public function can_save_and_retrieve_references()
    {
        $link = Facades\ExternalLink::make()
            ->url('https://example.com/broken')
            ->references([
                new Reference(type: 'entry', id: '1', site: 'en'),
            ]);

        $this->repo->save($link);

        $fresh = $this->repo->find($link->id());

        $this->assertCount(1, $fresh->references());
        $this->assertEquals('entry', $fresh->references()->first()->type);
    }

    #[Test]
    public function can_query_links_by_the_items_referencing_them()
    {
        $link = Facades\ExternalLink::make()
            ->url('https://example.com/broken')
            ->references([
                new Reference(type: 'entry', id: '1', site: 'en'),
            ]);

        $this->repo->save($link);

        $this->repo->save(Facades\ExternalLink::make()
            ->url('https://example.com/other')
            ->references([
                new Reference(type: 'entry', id: '2', site: 'en'),
            ]));

        $links = $this->repo->query()->whereJsonContains('references', (new Reference(type: 'entry', id: '1', site: 'en'))->key())->get();

        $this->assertEquals([$link->id()], $links->map->id()->all());
    }

    #[Test]
    public function can_delete_link()
    {
        $link = Facades\ExternalLink::make()->url('https://example.com/broken');
        $this->repo->save($link);

        $this->repo->delete($link);

        $this->assertDatabaseMissing('seo_pro_external_links', ['url' => 'https://example.com/broken']);
    }

    #[Test]
    public function saving_an_existing_link_updates_it_rather_than_duplicating()
    {
        $link = Facades\ExternalLink::make()->url('https://example.com/broken');
        $this->repo->save($link);

        $link->statusCode(404);
        $this->repo->save($link);

        $this->assertEquals(1, ExternalLinkModel::count());
        $this->assertEquals(404, ExternalLinkModel::first()->status_code);
    }
}
