<?php

namespace Tests\Redirects\Stache;

use PHPUnit\Framework\Attributes\Test;
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
}
