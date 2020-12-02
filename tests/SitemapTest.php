<?php

namespace Tests;

use Statamic\Facades\Collection;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;

class SitemapTest extends TestCase
{
    public function setUp(): void
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

        $today = now()->format('Y-m-d');

        $expected = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>http://cool-runnings.com</loc>
        <lastmod>2020-11-24</lastmod>
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
        <loc>http://cool-runnings.com/dance</loc>
        <lastmod>$today</lastmod>
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

        $this->assertStringContainsString('<loc>http://cool-runnings.com</loc>', $content);
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
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'priority' => 0.3,
            ]);

        $content = $this
            ->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $today = now()->format('Y-m-d');

        $this->assertStringContainsString(<<<EOT
    <url>
        <loc>http://cool-runnings.com/about</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
EOT,
            $content
        );

        $this->assertStringContainsString(<<<'EOT'
    <url>
        <loc>http://cool-runnings.com/articles</loc>
        <lastmod>2020-01-17</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.2</priority>
    </url>
EOT,
            $content
        );

        $this->assertStringContainsString(<<<EOT
    <url>
        <loc>http://cool-runnings.com/dance</loc>
        <lastmod>$today</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.1</priority>
    </url>
EOT,
            $content
        );
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
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'change_frequency' => 'hourly',
            ]);

        $content = $this
            ->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $today = now()->format('Y-m-d');

        $this->assertStringContainsString(<<<EOT
    <url>
        <loc>http://cool-runnings.com/about</loc>
        <lastmod>$today</lastmod>
        <changefreq>hourly</changefreq>
        <priority>0.5</priority>
    </url>
EOT,
            $content
        );

        $this->assertStringContainsString(<<<'EOT'
    <url>
        <loc>http://cool-runnings.com/articles</loc>
        <lastmod>2020-01-17</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.5</priority>
    </url>
EOT,
            $content
        );

        $this->assertStringContainsString(<<<EOT
    <url>
        <loc>http://cool-runnings.com/dance</loc>
        <lastmod>$today</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
EOT,
            $content
        );
    }

    protected function setCustomSitemapXmlUrl($app)
    {
        $app->config->set('statamic.seo-pro.sitemap', [
            'enabled' => true,
            'url' => 'gps.xml',
            'expire' => 60,
        ]);
    }
}
