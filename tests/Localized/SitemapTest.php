<?php

namespace Tests\Localized;

use Tests\TestCase;

class SitemapTest extends TestCase
{
    protected $siteFixturePath = __DIR__.'/../Fixtures/site-localized';

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('statamic.system.multisite', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->files->exists($folder = resource_path('views/vendor/seo-pro'))) {
            $this->files->deleteDirectory($folder);
        }

        $this->files->makeDirectory($folder, 0755, true);
    }

    protected function getPagesFromSitemapXml($content)
    {
        $data = json_decode(json_encode(simplexml_load_string($content)), true);

        return collect($data['url']);
    }

    /** @test */
    public function it_outputs_italian_sitemap_xml()
    {
        $content = $this
            ->get('http://corse-fantastiche.it/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertCount(2, $this->getPagesFromSitemapXml($content));

        $today = now()->format('Y-m-d');

        $expected = <<<'EOT'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <url>
        <loc>http://corse-fantastiche.it</loc>
        <lastmod>2021-09-20</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>http://corse-fantastiche.it/about</loc>
        <lastmod>2021-09-20</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

</urlset>

EOT;

        $this->assertEquals($expected, $content);
    }
}
