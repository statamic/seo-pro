<?php

namespace Tests\DeadLinks\Stache;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\YAML;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\DeadLinks\Stache\LinkRepository;
use Statamic\SeoPro\Facades;
use Statamic\Support\Str;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class LinkRepositoryTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(LinkRepository::class);
    }

    #[Test]
    public function can_find_link()
    {
        Facades\DeadLink::make()
            ->id('abc')
            ->url('https://cool-runnings.com/old-page')
            ->status(Link::STATUS_FAILING)
            ->statusCode(404)
            ->save();

        $link = $this->repo->find('abc');

        $this->assertInstanceOf(Link::class, $link);
        $this->assertEquals('abc', $link->id());
        $this->assertEquals('https://cool-runnings.com/old-page', $link->url());
        $this->assertEquals(Link::STATUS_FAILING, $link->status());
        $this->assertEquals(404, $link->statusCode());
    }

    #[Test]
    public function can_find_link_by_url()
    {
        Facades\DeadLink::make()->id('abc')->url('https://cool-runnings.com/old-page')->save();

        $this->assertEquals('abc', $this->repo->findByUrl('https://cool-runnings.com/old-page', 'default')->id());
        $this->assertNull($this->repo->findByUrl('https://cool-runnings.com/old-page', 'fr'));
        $this->assertNull($this->repo->findByUrl('https://cool-runnings.com/unknown', 'default'));
    }

    #[Test]
    public function can_save_link()
    {
        $link = Facades\DeadLink::make()
            ->id('abc')
            ->url('https://cool-runnings.com/old-page')
            ->status(Link::STATUS_OK);

        $this->repo->save($link);

        $this->assertStringContainsString('storage/statamic/seopro/dead-links/abc.yaml', $link->path());

        $yaml = YAML::file($link->path())->parse();

        $this->assertEquals('https://cool-runnings.com/old-page', $yaml['url']);
        $this->assertEquals('ok', $yaml['status']);
    }

    #[Test]
    public function it_generates_a_uuid_when_saving_without_id()
    {
        $link = Facades\DeadLink::make()->url('https://cool-runnings.com/old-page');

        $this->repo->save($link);

        $this->assertTrue(Str::isUuid($link->id()));
    }

    #[Test]
    public function can_save_link_with_a_long_url()
    {
        $link = Facades\DeadLink::make()->url('https://example.com/'.str_repeat('a', 300));

        $this->repo->save($link);

        $this->assertFileExists($link->path());
    }

    #[Test]
    public function it_saves_and_retrieves_references()
    {
        $link = Facades\DeadLink::make()
            ->id('abc')
            ->url('https://cool-runnings.com/old-page')
            ->references([
                ['subject_type' => 'entry', 'subject_id' => '1', 'site' => 'en', 'field_path' => 'body', 'title' => 'Home', 'edit_url' => '/cp/x'],
            ]);

        $this->repo->save($link);

        $fresh = $this->repo->find('abc');

        $this->assertCount(1, $fresh->references());
        $this->assertEquals('entry', $fresh->references()->first()['subject_type']);
    }

    #[Test]
    public function can_query_links_by_referenced_subject()
    {
        Facades\DeadLink::make()
            ->id('abc')
            ->url('https://cool-runnings.com/old-page')
            ->references([
                ['subject_type' => 'entry', 'subject_id' => '1', 'site' => 'en', 'field_path' => 'body', 'title' => 'Home', 'edit_url' => '/cp/x'],
            ])
            ->save();

        Facades\DeadLink::make()
            ->id('def')
            ->url('https://cool-runnings.com/other-page')
            ->references([
                ['subject_type' => 'entry', 'subject_id' => '2', 'site' => 'en', 'field_path' => 'body', 'title' => 'About', 'edit_url' => '/cp/y'],
            ])
            ->save();

        $links = $this->repo->query()->whereJsonContains('subjects', Link::subjectKey('entry', '1', 'en'))->get();

        $this->assertEquals(['abc'], $links->map->id()->all());
    }

    #[Test]
    public function can_delete_link()
    {
        $link = Facades\DeadLink::make()
            ->id('abc')
            ->url('https://cool-runnings.com/old-page');

        $link->save();

        $this->assertFileExists($link->path());

        $this->repo->delete($link);

        $this->assertFileDoesNotExist($link->path());
    }
}
