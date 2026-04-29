<?php

namespace Tests\Redirects\Stache;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\SeoPro\Redirects\Stache\RedirectRepository;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class RedirectRepositoryTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(RedirectRepository::class);
    }

    #[Test]
    public function can_find_redirect()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $redirect = $this->repo->find('abc');

        $this->assertInstanceOf(Redirect::class, $redirect);
        $this->assertEquals('abc', $redirect->id());
        $this->assertEquals('https://cool-runnings.com/old-url', $redirect->source());
        $this->assertEquals('https://cool-runnings.com/new-url', $redirect->destination());
        $this->assertEquals(302, $redirect->responseCode());
        $this->assertTrue($redirect->enabled());
    }

    #[Test]
    public function can_save_redirect()
    {
        $redirect = Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true);

        $this->repo->save($redirect);

        $this->assertStringContainsString('content/seo-pro/redirects/abc.yaml', $redirect->path());

        $yaml = YAML::file($redirect->path())->parse();

        $this->assertEquals('https://cool-runnings.com/old-url', $yaml['source']);
        $this->assertEquals('https://cool-runnings.com/new-url', $yaml['destination']);
        $this->assertEquals(302, $yaml['response_code']);
        $this->assertTrue($yaml['enabled']);
    }

    #[Test]
    public function it_generates_id_from_source_when_saving_without_id()
    {
        $redirect = Facades\Redirect::make()
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true);

        $this->repo->save($redirect);

        $this->assertEquals('old-url', $redirect->id());
    }

    #[Test]
    public function it_appends_suffix_when_generated_id_already_exists()
    {
        Facades\Redirect::make()
            ->id('old-url')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $redirect = Facades\Redirect::make()
            ->source('/old-url')
            ->destination('/other-url')
            ->responseCode(301)
            ->enabled(true);

        $this->repo->save($redirect);

        $this->assertEquals('old-url-1', $redirect->id());
    }

    #[Test]
    public function it_increments_suffix_when_multiple_duplicates_exist()
    {
        Facades\Redirect::make()
            ->id('old-url')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        Facades\Redirect::make()
            ->id('old-url-1')
            ->source('/old-url')
            ->destination('/other-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $redirect = Facades\Redirect::make()
            ->source('/old-url')
            ->destination('/another-url')
            ->responseCode(301)
            ->enabled(true);

        $this->repo->save($redirect);

        $this->assertEquals('old-url-2', $redirect->id());
    }

    #[Test]
    public function it_does_not_change_existing_id_when_saving()
    {
        $redirect = Facades\Redirect::make()
            ->id('my-custom-id')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true);

        $this->repo->save($redirect);

        $this->assertEquals('my-custom-id', $redirect->id());
    }

    #[Test]
    public function can_delete_redirect()
    {
        $redirect = Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true);

        $redirect->save();

        $this->assertFileExists($redirect->path());

        $this->repo->delete($redirect);

        $this->assertFileDoesNotExist($redirect->path());
    }
}
