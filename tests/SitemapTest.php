<?php

namespace Tests;

use Illuminate\Support\Collection as IlluminateCollection;
use Statamic\Console\Composer\Lock;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;
use Statamic\SeoPro\Sitemap\Sitemap;
use Statamic\Statamic;

class SitemapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->files->exists($folder = resource_path('views/vendor/seo-pro'))) {
            $this->files->deleteDirectory($folder);
        }

        $this->files->makeDirectory($folder, 0755, true);
    }

    /** @test */
    public function it_outputs_sitemap_xml()
    {
        $content = $this
            ->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertCount(7, $this->getPagesFromSitemapXml($content));

        $today = now()->format('Y-m-d');

        $expected = <<<"EOT"
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc>http://cool-runnings.com</loc>
        <lastmod>2020-11-24</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/about</loc>
        <lastmod>2020-01-17</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/articles</loc>
        <lastmod>2020-01-17</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/dance</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/magic</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/nectar</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/topics</loc>
        <lastmod>2020-01-20</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

</urlset>

EOT;

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_404s_when_sitemap_xml_is_disabled()
    {
        Config::set('statamic.seo-pro.sitemap.enabled', false);

        $content = $this
            ->get('/sitemap.xml')
            ->assertStatus(404);
    }

    /**
     * @test
     *
     * @environment-setup setCustomSitemapXmlUrl
     */
    public function it_outputs_sitemap_xml_with_custom_url()
    {
        $this
            ->get('/sitemap.xml')
            ->assertStatus(404);

        $content = $this
            ->get('/gps.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsStringIgnoringLineEndings('<loc>http://cool-runnings.com</loc>', $content);
    }

    /** @test */
    public function it_outputs_sitemap_xml_with_custom_view()
    {
        $this->files->put(resource_path('views/vendor/seo-pro/sitemap.antlers.html'), '{{ xml_header }} test');

        $content = $this
            ->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertEquals('<?xml version="1.0" encoding="UTF-8"?> test', $content);
    }

    /** @test */
    public function it_uses_cascade_to_generate_priorities()
    {
        $this
            ->setSeoInSiteDefaults([
                'priority' => 0.1,
            ])
            ->setSeoOnCollection(Collection::find('pages'), [
                'priority' => 0.2,
            ])
            ->setSeoOnEntry(Entry::findByUri('/about')->entry(), [
                'priority' => 0.3,
            ]);

        $content = $this
            ->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $priorities = $this->getPagesFromSitemapXml($content)->pluck('priority', 'loc');

        $this->assertEquals('0.3', $priorities->get('http://cool-runnings.com/about'));
        $this->assertEquals('0.2', $priorities->get('http://cool-runnings.com/articles'));
        $this->assertEquals('0.1', $priorities->get('http://cool-runnings.com/dance'));
    }

    /** @test */
    public function it_uses_cascade_to_generate_frequencies()
    {
        $this
            ->setSeoInSiteDefaults([
                'change_frequency' => 'weekly',
            ])
            ->setSeoOnCollection(Collection::find('pages'), [
                'change_frequency' => 'daily',
            ])
            ->setSeoOnEntry(Entry::findByUri('/about')->entry(), [
                'change_frequency' => 'hourly',
            ]);

        $content = $this
            ->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $frequencies = $this->getPagesFromSitemapXml($content)->pluck('changefreq', 'loc');

        $this->assertEquals('hourly', $frequencies->get('http://cool-runnings.com/about'));
        $this->assertEquals('daily', $frequencies->get('http://cool-runnings.com/articles'));
        $this->assertEquals('weekly', $frequencies->get('http://cool-runnings.com/dance'));
    }

    /** @test */
    public function it_doesnt_generate_pages_for_content_without_uris()
    {
        $this->files->put(base_path('content/collections/articles.yaml'), 'route: null');

        $content = $this
            ->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertCount(4, $this->getPagesFromSitemapXml($content));
    }

    protected function setCustomSitemapXmlUrl($app)
    {
        $app->config->set('statamic.seo-pro.sitemap', [
            'enabled' => true,
            'url' => 'gps.xml',
            'expire' => 60,
        ]);
    }

    protected function getPagesFromSitemapXml($content)
    {
        $data = json_decode(json_encode(simplexml_load_string($content)), true);

        return collect($data['url']);
    }

    /** @test */
    public function it_outputs_paginated_sitemap_index_xml()
    {
        config()->set('statamic.seo-pro.sitemap.pagination.enabled', true);
        config()->set('statamic.seo-pro.sitemap.pagination.limit', 5);

        $content = $this
            ->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $expected = <<<'EOT'
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <sitemap>
        <loc>http://cool-runnings.com/sitemap_1.xml</loc>
    </sitemap>

    <sitemap>
        <loc>http://cool-runnings.com/sitemap_2.xml</loc>
    </sitemap>

</sitemapindex>

EOT;

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_outputs_paginated_sitemap_page_xml()
    {
        config()->set('statamic.seo-pro.sitemap.pagination.enabled', true);
        config()->set('statamic.seo-pro.sitemap.pagination.limit', 5);

        $content = $this
            ->get('/sitemap_1.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertCount(5, $this->getPagesFromSitemapXml($content));

        $today = now()->format('Y-m-d');

        $expected = <<<"EOT"
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc>http://cool-runnings.com</loc>
        <lastmod>2020-11-24</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/about</loc>
        <lastmod>2020-01-17</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/articles</loc>
        <lastmod>2020-01-17</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/dance</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/magic</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

</urlset>

EOT;

        $this->assertEquals($expected, $content);

        $content = $this
            ->get('/sitemap_2.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertCount(2, $this->getPagesFromSitemapXml($content));

        $today = now()->format('Y-m-d');

        $expected = <<<"EOT"
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc>http://cool-runnings.com/nectar</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/topics</loc>
        <lastmod>2020-01-20</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

</urlset>

EOT;

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_404s_on_invalid_pagination_urls()
    {
        config()->set('statamic.seo-pro.sitemap.pagination.enabled', true);
        config()->set('statamic.seo-pro.sitemap.pagination.limit', 5);

        $this
            ->get('/sitemap_3.xml')
            ->assertNotFound();

        $this
            ->get('/sitemap_3a.xml')
            ->assertNotFound();

        $this
            ->get('/sitemap_a.xml')
            ->assertNotFound();
    }

    /** @test */
    public function it_can_use_custom_sitemap_queries()
    {
        // Hacky/temporary version compare, because `reorder()` method we're using
        // in CustomSitemap class below requires 5.29.0+, and we don't want to
        // increase minimum required Statamic version just for test setup
        if (version_compare(ltrim(Lock::file(__DIR__.'/../composer.lock')->getInstalledVersion('statamic/cms'), 'v'), '5.29.0', '<')) {
            $this->markTestSkipped();
        }

        app()->bind(Sitemap::class, CustomSitemap::class);

        config()->set('statamic.seo-pro.sitemap.pagination.enabled', true);
        config()->set('statamic.seo-pro.sitemap.pagination.limit', 5);

        $this->assertNull(Blink::get('ran-custom-entries-query'));
        $this->assertNull(Blink::get('ran-custom-entries-for-page-query'));

        $content = $this
            ->get('/sitemap_1.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertEquals(2, Blink::get('ran-custom-entries-query'));
        $this->assertEquals(1, Blink::get('ran-custom-entries-for-page-query'));
        $this->assertCount(5, $this->getPagesFromSitemapXml($content));

        $today = now()->format('Y-m-d');

        $expected = <<<"EOT"
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc>http://cool-runnings.com/articles</loc>
        <lastmod>2020-01-17</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/dance</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/magic</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/nectar</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/topics</loc>
        <lastmod>2020-01-20</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

</urlset>

EOT;

        $this->assertEquals($expected, $content);

        $content = $this
            ->get('/sitemap_2.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertEquals(4, Blink::get('ran-custom-entries-query'));
        $this->assertEquals(2, Blink::get('ran-custom-entries-for-page-query'));
        $this->assertCount(2, $this->getPagesFromSitemapXml($content));

        $today = now()->format('Y-m-d');

        $expected = <<<'EOT'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc>http://cool-runnings.com</loc>
        <lastmod>2020-11-24</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/about</loc>
        <lastmod>2020-01-17</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

</urlset>

EOT;

        $this->assertEquals($expected, $content);
    }
}

class CustomSitemap extends Sitemap
{
    protected function publishedEntriesQuery()
    {
        // count how many times this is called
        Blink::increment('ran-custom-entries-query');

        // reversing from default order, just so we can assert query has effect on output
        return parent::publishedEntriesQuery()->reorder('uri', 'desc');
    }

    protected function publishedEntriesForPage(int $page, int $perPage): IlluminateCollection
    {
        // count how many times this is called
        Blink::increment('ran-custom-entries-for-page-query');

        // also reverse items on individual pages, again so we can assert query has effect on output
        return parent::publishedEntriesForPage($page, $perPage)->reverse();
    }
}
