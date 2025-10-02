<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\Test;

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

    private function getPagesFromSitemapXml($content)
    {
        $data = json_decode(json_encode(simplexml_load_string($content)), true);

        return collect($data['url']);
    }
}
