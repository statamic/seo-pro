<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

class SitemapTest extends LocalizedTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->files->exists($folder = resource_path('views/vendor/seo-pro'))) {
            $this->files->deleteDirectory($folder);
        }

        $this->files->makeDirectory($folder, 0755, true);
    }

    #[Test]
    public function it_outputs_italian_sitemap_xml()
    {
        $content = $this
            ->get('http://corse-fantastiche.it/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertCount(2, $this->getPagesFromSitemapXml($content));

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

    #[Test]
    public function it_uses_localized_site_defaults_in_sitemap_xml()
    {
        // There are multiple sites on the cool-runnings.com domain.
        // Pages from each site should return a different "changefreq" and "priority" (as per their site defaults).

        SiteDefaults::in('default')->set('change_frequency', 'monthly')->set('priority', 0.2)->save();
        SiteDefaults::in('french')->set('change_frequency', 'weekly')->set('priority', 0.4)->save();
        SiteDefaults::in('british')->set('change_frequency', 'daily')->set('priority', 1)->save();

        $content = $this
            ->get('http://cool-runnings.com/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertCount(11, $this->getPagesFromSitemapXml($content));

        $expected = <<<'EOT'
    <url>
        <loc>http://cool-runnings.com</loc>
        <lastmod>2020-11-24</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.2</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/en-gb</loc>
        <lastmod>2021-09-20</lastmod>
        <changefreq>daily</changefreq>
        <priority>1</priority>
    </url>

    <url>
        <loc>http://cool-runnings.com/fr</loc>
        <lastmod>2021-09-20</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.4</priority>
    </url>
EOT;

        $this->assertStringContainsStringIgnoringLineEndings($expected, $content);
    }

    private function getPagesFromSitemapXml($content)
    {
        $data = json_decode(json_encode(simplexml_load_string($content)), true);

        return collect($data['url']);
    }
}
