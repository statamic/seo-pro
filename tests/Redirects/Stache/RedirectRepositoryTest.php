<?php

namespace Redirects\Stache;

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
            ->sourceUrl('https://cool-runnings.com/old-url')
            ->destinationUrl('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $redirect = $this->repo->find('abc');

        $this->assertInstanceOf(Redirect::class, $redirect);
        $this->assertEquals('abc', $redirect->id());
        $this->assertEquals('https://cool-runnings.com/old-url', $redirect->sourceUrl());
        $this->assertEquals('https://cool-runnings.com/new-url', $redirect->destinationUrl());
        $this->assertEquals(302, $redirect->responseCode());
        $this->assertTrue($redirect->enabled());
    }

    #[Test]
    public function can_save_redirect()
    {
        $redirect = Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('https://cool-runnings.com/old-url')
            ->destinationUrl('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true);

        $this->repo->save($redirect);

        $this->assertStringContainsString('content/seo-pro/redirects/abc.yaml', $redirect->path());

        $yaml = YAML::file($redirect->path())->parse();

        $this->assertEquals('https://cool-runnings.com/old-url', $yaml['source_url']);
        $this->assertEquals('https://cool-runnings.com/new-url', $yaml['destination_url']);
        $this->assertEquals(302, $yaml['response_code']);
        $this->assertTrue($yaml['enabled']);
    }

    #[Test]
    public function can_delete_redirect()
    {
        $redirect = Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('https://cool-runnings.com/old-url')
            ->destinationUrl('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true);

        $redirect->save();

        $this->assertFileExists($redirect->path());

        $this->repo->delete($redirect);

        $this->assertFileDoesNotExist($redirect->path());
    }
}
