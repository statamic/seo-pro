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

        // Otherwise, terms won't be returned in the sitemap.
        $this->files->ensureDirectoryExists(resource_path('views/topics'));
        $this->files->put(resource_path('views/topics/show.antlers.html'), '');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->files->deleteDirectory(resource_path('views/topics'));
    }

    #[Test]
    public function it_outputs_italian_sitemap_xml()
    {
        $this
            ->get('http://corse-fantastiche.it/sitemap.xml')
            ->assertOk()
            ->assertContentType('text/xml; charset=UTF-8')
            ->assertSeeInOrder([
                '<?xml version="1.0" encoding="UTF-8"?>',
                '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">',
                '<url>',
                '<loc>http://corse-fantastiche.it</loc>',
                '<lastmod>2021-09-20</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com" />',
                '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr" />',
                '<xhtml:link rel="alternate" hreflang="it-it" href="http://corse-fantastiche.it" />',
                '<xhtml:link rel="alternate" hreflang="en-gb" href="http://cool-runnings.com/en-gb" />',
                '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com" />',
                '</url>',
                '<url>',
                '<loc>http://corse-fantastiche.it/about</loc>',
                '<lastmod>2021-09-20</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.5</priority>',
                '</url>',
                '</urlset>',
            ], escape: false)
            ->assertDontSee('<loc>http://cool-runnings.com</loc>', escape: false)
            ->assertDontSee('<loc>http://cool-runnings.com/articles/topics/sneakers</loc>', escape: false);
    }

    #[Test]
    public function it_outputs_default_sitemap_xml()
    {
        $this
            ->get('http://cool-runnings.com/sitemap.xml')
            ->assertOk()
            ->assertContentType('text/xml; charset=UTF-8')
            ->assertSeeInOrder($this->getDefaultSiteSitemapXml(), escape: false)
            ->assertDontSee('<loc>http://corse-fantastiche.it</loc>', escape: false)
            ->assertDontSee('<loc>http://corse-fantastiche.it/about</loc>', escape: false);
    }

    private function getDefaultSiteSitemapXml(): array
    {
        $today = now()->format('Y-m-d');

        return [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">',
            '<url>',
            '<loc>http://cool-runnings.com</loc>',
            '<lastmod>2020-11-24</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com" />',
            '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr" />',
            '<xhtml:link rel="alternate" hreflang="it-it" href="http://corse-fantastiche.it" />',
            '<xhtml:link rel="alternate" hreflang="en-gb" href="http://cool-runnings.com/en-gb" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/en-gb</loc>',
            '<lastmod>2021-09-20</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com" />',
            '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr" />',
            '<xhtml:link rel="alternate" hreflang="it-it" href="http://corse-fantastiche.it" />',
            '<xhtml:link rel="alternate" hreflang="en-gb" href="http://cool-runnings.com/en-gb" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/fr</loc>',
            '<lastmod>2021-09-20</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com" />',
            '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr" />',
            '<xhtml:link rel="alternate" hreflang="it-it" href="http://corse-fantastiche.it" />',
            '<xhtml:link rel="alternate" hreflang="en-gb" href="http://cool-runnings.com/en-gb" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/about</loc>',
            '<lastmod>2020-01-17</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/about" />',
            '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/about" />',
            '<xhtml:link rel="alternate" hreflang="it-it" href="http://corse-fantastiche.it/about" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/about" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/articles</loc>',
            '<lastmod>2020-01-17</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/articles" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/articles" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/dance</loc>',
            '<lastmod>'.$today.'</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/dance" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/dance" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/magic</loc>',
            '<lastmod>'.$today.'</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/magic" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/magic" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/nectar</loc>',
            '<lastmod>'.$today.'</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/nectar" />',
            '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/nectar" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/nectar" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/topics</loc>',
            '<lastmod>2020-01-20</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/topics" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/topics" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/fr/about</loc>',
            '<lastmod>2021-09-20</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/about" />',
            '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/about" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/about" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/fr/nectar</loc>',
            '<lastmod>2021-09-20</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/nectar" />',
            '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/nectar" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/nectar" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/articles/topics/sneakers</loc>',
            '<lastmod>2020-01-17</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/articles/topics/sneakers" />',
            '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/articles/topics/sneakers" />',
            '<xhtml:link rel="alternate" hreflang="en-gb" href="http://cool-runnings.com/en-gb/articles/topics/sneakers" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/articles/topics/sneakers" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/articles/topics/soda</loc>',
            '<lastmod>2020-01-17</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/articles/topics/soda" />',
            '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/articles/topics/soda" />',
            '<xhtml:link rel="alternate" hreflang="en-gb" href="http://cool-runnings.com/en-gb/articles/topics/soda" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/articles/topics/soda" />',
            '</url>',
            '<url>',
            '<loc>http://cool-runnings.com/articles/topics/dance</loc>',
            '<lastmod>2020-01-17</lastmod>',
            '<changefreq>monthly</changefreq>',
            '<priority>0.5</priority>',
            '<xhtml:link rel="alternate" hreflang="en-us" href="http://cool-runnings.com/articles/topics/dance" />',
            '<xhtml:link rel="alternate" hreflang="fr-fr" href="http://cool-runnings.com/fr/articles/topics/dance" />',
            '<xhtml:link rel="alternate" hreflang="en-gb" href="http://cool-runnings.com/en-gb/articles/topics/dance" />',
            '<xhtml:link rel="alternate" hreflang="x-default" href="http://cool-runnings.com/articles/topics/dance" />',
            '</url>',
            '</urlset>',
        ];
    }
}
