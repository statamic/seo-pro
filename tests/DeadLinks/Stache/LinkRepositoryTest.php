<?php

namespace Tests\DeadLinks\Stache;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\YAML;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\DeadLinks\Stache\LinkRepository;
use Statamic\SeoPro\Facades;
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
    public function it_generates_id_from_url_when_saving_without_id()
    {
        $link = Facades\DeadLink::make()->url('https://cool-runnings.com/old-page');

        $this->repo->save($link);

        $this->assertNotEmpty($link->id());
    }

    #[Test]
    public function it_appends_suffix_when_generated_id_already_exists()
    {
        Facades\DeadLink::make()->id('page')->url('https://a.com/page')->save();

        $link = Facades\DeadLink::make()->url('https://b.com/page');

        $this->repo->save($link);

        $this->assertNotEquals('page', $link->id());
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
