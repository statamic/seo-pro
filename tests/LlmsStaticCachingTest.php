<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Site;
use Statamic\SeoPro\Llms\Llms;
use Statamic\SeoPro\Llms\LlmsDocument;
use Statamic\StaticCaching\NoCache\Session;
use Statamic\StaticCaching\StaticCacheManager;

class LlmsStaticCachingTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->files->deleteDirectory(public_path('static'));

        parent::tearDown();
    }

    #[Test]
    public function half_measure_caching_preserves_the_plain_text_response()
    {
        $this->useStaticCaching('half');
        $this->enableLlmsTxt();

        $this->get('/llms.txt')
            ->assertOk()
            ->assertContentType('text/plain; charset=UTF-8')
            ->assertContent("# Cool Runnings\n");

        $response = $this->get('/llms.txt')
            ->assertOk()
            ->assertContentType('text/plain; charset=UTF-8')
            ->assertContent("# Cool Runnings\n");

        $this->assertTrue($response->baseResponse->wasStaticallyCached());
    }

    #[Test]
    public function full_measure_does_not_write_an_html_cache_file_for_the_text_route()
    {
        $this->useStaticCaching('full');
        $this->enableLlmsTxt();

        $this->get('/llms.txt')
            ->assertOk()
            ->assertContentType('text/plain; charset=UTF-8')
            ->assertHeader('X-Statamic-Uncacheable', 'true')
            ->assertContent("# Cool Runnings\n");

        $this->get('/llms.txt')
            ->assertOk()
            ->assertContentType('text/plain; charset=UTF-8')
            ->assertHeader('X-Statamic-Uncacheable', 'true');

        $this->assertFileDoesNotExist(public_path('static/llms.txt.html'));
    }

    private function useStaticCaching(string $strategy): void
    {
        config()->set('statamic.static_caching.strategy', $strategy);
        app()->forgetInstance(StaticCacheManager::class);
        app()->forgetInstance(Session::class);
    }

    private function enableLlmsTxt(): void
    {
        Llms::saveWithoutGenerated(new LlmsDocument([
            'enabled' => true,
            'title' => 'Cool Runnings',
        ]), Site::default());
    }
}
