<?php

namespace Tests\BrokenLinks\Stache;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\YAML;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\BrokenLinks\Reference;
use Statamic\SeoPro\BrokenLinks\Stache\ExternalLinkRepository;
use Statamic\SeoPro\Facades;
use Statamic\Support\Str;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ExternalLinkRepositoryTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(ExternalLinkRepository::class);
    }

    #[Test]
    public function can_find_link()
    {
        Facades\ExternalLink::make()
            ->id('abc')
            ->url('https://cool-runnings.com/old-page')
            ->brokenSince(now())
            ->statusCode(404)
            ->save();

        $link = $this->repo->find('abc');

        $this->assertInstanceOf(ExternalLink::class, $link);
        $this->assertEquals('abc', $link->id());
        $this->assertEquals('https://cool-runnings.com/old-page', $link->url());
        $this->assertNotNull($link->brokenSince());
        $this->assertEquals(404, $link->statusCode());
    }

    #[Test]
    public function can_find_link_by_url()
    {
        Facades\ExternalLink::make()->id('abc')->url('https://cool-runnings.com/old-page')->save();

        $this->assertEquals('abc', $this->repo->findByUrl('https://cool-runnings.com/old-page', 'default')->id());
        $this->assertNull($this->repo->findByUrl('https://cool-runnings.com/old-page', 'fr'));
        $this->assertNull($this->repo->findByUrl('https://cool-runnings.com/unknown', 'default'));
    }

    #[Test]
    public function can_save_link()
    {
        $link = Facades\ExternalLink::make()
            ->id('abc')
            ->url('https://cool-runnings.com/old-page')
            ->checkedAt(now());

        $this->repo->save($link);

        $this->assertStringContainsString('storage/statamic/seopro/external-links/abc.yaml', $link->path());

        $yaml = YAML::file($link->path())->parse();

        $this->assertEquals('https://cool-runnings.com/old-page', $yaml['url']);
        $this->assertArrayHasKey('checked_at', $yaml);
    }

    #[Test]
    public function it_generates_a_uuid_when_saving_without_id()
    {
        $link = Facades\ExternalLink::make()->url('https://cool-runnings.com/old-page');

        $this->repo->save($link);

        $this->assertTrue(Str::isUuid($link->id()));
    }

    #[Test]
    public function can_save_link_with_a_long_url()
    {
        $link = Facades\ExternalLink::make()->url('https://example.com/'.str_repeat('a', 300));

        $this->repo->save($link);

        $this->assertFileExists($link->path());
    }

    #[Test]
    public function it_saves_and_retrieves_references()
    {
        $link = Facades\ExternalLink::make()
            ->id('abc')
            ->url('https://cool-runnings.com/old-page')
            ->references([
                new Reference(type: 'entry', id: '1', site: 'en'),
            ]);

        $this->repo->save($link);

        $fresh = $this->repo->find('abc');

        $this->assertCount(1, $fresh->references());
        $this->assertEquals('entry', $fresh->references()->first()->type);
    }

    #[Test]
    public function can_query_links_by_the_items_referencing_them()
    {
        Facades\ExternalLink::make()
            ->id('abc')
            ->url('https://cool-runnings.com/old-page')
            ->references([
                new Reference(type: 'entry', id: '1', site: 'en'),
            ])
            ->save();

        Facades\ExternalLink::make()
            ->id('def')
            ->url('https://cool-runnings.com/other-page')
            ->references([
                new Reference(type: 'entry', id: '2', site: 'en'),
            ])
            ->save();

        $links = $this->repo->query()->whereJsonContains('subjects', (new Reference(type: 'entry', id: '1', site: 'en'))->key())->get();

        $this->assertEquals(['abc'], $links->map->id()->all());
    }

    #[Test]
    public function can_delete_link()
    {
        $link = Facades\ExternalLink::make()
            ->id('abc')
            ->url('https://cool-runnings.com/old-page');

        $link->save();

        $this->assertFileExists($link->path());

        $this->repo->delete($link);

        $this->assertFileDoesNotExist($link->path());
    }
}
