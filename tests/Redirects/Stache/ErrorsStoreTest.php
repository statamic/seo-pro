<?php

namespace Tests\Redirects\Stache;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Site;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Error;
use Statamic\Stache\Stores\Store;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ErrorsStoreTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    private Store $store;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = app('stache')->store('seo_pro_errors');
        $this->directory = config('statamic.seo-pro.redirects.errors.directory');
    }

    #[Test]
    public function it_makes_error_instances_from_files()
    {
        $item = $this->store->makeItemFromFile(
            $this->directory.'/abc.yaml',
            "url: /broken-link\nhits: 5\nlast_hit_at: '2026-04-21 12:00:00'",
        );

        $this->assertInstanceOf(Error::class, $item);
        $this->assertEquals('abc', $item->id());
        $this->assertEquals('/broken-link', $item->url());
        $this->assertEquals(5, $item->hits());
        $this->assertEquals('2026-04-21 12:00:00', $item->lastHitAt());
    }

    #[Test]
    public function it_uses_defaults_for_missing_fields()
    {
        $item = $this->store->makeItemFromFile(
            $this->directory.'/abc.yaml',
            'url: /broken-link',
        );

        $this->assertInstanceOf(Error::class, $item);
        $this->assertEquals('/broken-link', $item->url());
        $this->assertEquals(0, $item->hits());
        $this->assertNull($item->lastHitAt());
    }

    #[Test]
    public function it_saves_to_disk()
    {
        $error = Facades\Error::make()
            ->id('abc')
            ->url('/broken-link')
            ->hits(3)
            ->lastHitAt('2026-04-21 12:00:00');

        $this->store->save($error);

        $path = $this->store->paths()->get('abc');

        $this->assertStringContainsString('errors/abc.yaml', $path);
        $this->assertStringEqualsFile($path, $error->fileContents());
        @unlink($path);
        $this->assertFileDoesNotExist($path);
    }

    #[Test]
    public function it_returns_id_as_item_key_for_single_site()
    {
        $error = Facades\Error::make()->id('abc')->site('en')->url('/broken-link');

        $key = $this->store->getItemKey($error);

        $this->assertEquals('abc', $key);
    }

    #[Test]
    public function it_returns_composite_key_for_multisite()
    {
        config(['statamic.system.multisite' => true]);

        Site::setSites([
            'en' => ['url' => 'http://test.com/', 'locale' => 'en_US'],
            'fr' => ['url' => 'http://test.com/fr/', 'locale' => 'fr_FR'],
        ]);

        $errorEn = Facades\Error::make()->id('abc')->site('en')->url('/broken-link');
        $errorFr = Facades\Error::make()->id('abc')->site('fr')->url('/broken-link');

        $this->assertEquals('en::abc', $this->store->getItemKey($errorEn));
        $this->assertEquals('fr::abc', $this->store->getItemKey($errorFr));
    }

    #[Test]
    public function it_extracts_site_from_multisite_path()
    {
        config(['statamic.system.multisite' => true]);

        Site::setSites([
            'en' => ['url' => 'http://test.com/', 'locale' => 'en_US'],
            'fr' => ['url' => 'http://test.com/fr/', 'locale' => 'fr_FR'],
        ]);

        $item = $this->store->makeItemFromFile(
            $this->store->directory().'fr/abc.yaml',
            "url: /broken-link\nhits: 5",
        );

        $this->assertEquals('abc', $item->id());
        $this->assertEquals('fr', $item->site());
    }
}
