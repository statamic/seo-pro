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
    public function it_outputs_default_sitemap_xml()
    {
        $content = $this
            ->get('http://cool-runnings.com/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
/*            ->assertSeeInOrder([
                '<loc>http://cool-runnings.com</loc>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com"/>',
                '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr"/>',
                '<xhtml:link rel="alternate" hreflang="en-gb" href="http://cool-runnings.com/en-gb"/>',
                '<loc>http://cool-runnings.com/en-gb</loc>',
                '<lastmod>2021-09-20</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com"/>',
                '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr"/>',
                '<xhtml:link rel="alternate" hreflang="en-gb" href="http://cool-runnings.com/en-gb"/>',
                '<loc>http://cool-runnings.com/fr</loc>',
                '<lastmod>2021-09-20</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com"/>',
                '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr"/>',
                '<xhtml:link rel="alternate" hreflang="en-gb" href="http://cool-runnings.com/en-gb"/>',
                '<loc>http://cool-runnings.com/about</loc>',
                '<lastmod>2020-01-17</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/about"/>',
                '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/about"/>',
                '<loc>http://cool-runnings.com/articles</loc>',
                '<lastmod>2020-01-17</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/articles"/>',
                '<loc>http://cool-runnings.com/dance</loc>',
                '<lastmod>2025-08-14</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/dance"/>',
                '<loc>http://cool-runnings.com/magic</loc>',
                '<lastmod>2025-08-14</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/magic"/>',
                '<loc>http://cool-runnings.com/nectar</loc>',
                '<lastmod>2025-08-14</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/nectar"/>',
                '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/nectar"/>',
                '<loc>http://cool-runnings.com/topics</loc>',
                '<lastmod>2020-01-20</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/topics"/>',
                '<loc>http://cool-runnings.com/fr/about</loc>',
                '<lastmod>2021-09-20</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/about"/>',
                '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/about"/>',
                '<loc>http://cool-runnings.com/fr/nectar</loc>',
                '<lastmod>2021-09-20</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/nectar"/>',
                '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/nectar"/>',
            ])*/
            ->getContent();

        ray($content);
    }

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
