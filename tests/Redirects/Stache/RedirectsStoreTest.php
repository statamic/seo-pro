<?php

namespace Tests\Redirects\Stache;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\Stache\Stores\Store;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class RedirectsStoreTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    private Store $store;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = app('stache')->store('redirects');
        $this->directory = config('statamic.seo-pro.redirects.directory');
    }

    #[Test]
    public function it_makes_redirect_instances_from_files()
    {
        $item = $this->store->makeItemFromFile(
            $this->directory.'/abc.yaml',
            "source_url: 'https://cool-runnings.com/old-url'\ndestination_url: 'https://cool-runnings.com/new-url'\nresponse_code: 302\nenabled: true",
        );

        $this->assertInstanceOf(Redirect::class, $item);
        $this->assertEquals('abc', $item->id());
        $this->assertEquals('https://cool-runnings.com/old-url', $item->sourceUrl());
        $this->assertEquals('https://cool-runnings.com/new-url', $item->destinationUrl());
        $this->assertEquals(302, $item->responseCode());
        $this->assertTrue($item->enabled());
    }

    #[Test]
    public function it_uses_defaults_for_missing_fields()
    {
        $item = $this->store->makeItemFromFile(
            $this->directory.'/abc.yaml',
            "source_url: 'https://cool-runnings.com/old-url'",
        );

        $this->assertInstanceOf(Redirect::class, $item);
        $this->assertEquals('https://cool-runnings.com/old-url', $item->sourceUrl());
        $this->assertNull($item->destinationUrl());
        $this->assertEquals(301, $item->responseCode());
        $this->assertTrue($item->enabled());
    }

    #[Test]
    public function it_saves_to_disk()
    {
        $redirect = Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('https://cool-runnings.com/old-url')
            ->destinationUrl('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true);

        $this->store->save($redirect);

        $path = $this->store->paths()->get('abc');

        $this->assertStringContainsString('redirects/abc.yaml', $path);
        $this->assertStringEqualsFile($path, $redirect->fileContents());
        @unlink($path);
        $this->assertFileDoesNotExist($path);
    }
}
