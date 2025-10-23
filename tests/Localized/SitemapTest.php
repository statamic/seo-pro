<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\URL;
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

        // Otherwise, terms won't be returned in the sitemap.
        $this->files->ensureDirectoryExists(resource_path('views/topics'));
        $this->files->put(resource_path('views/topics/show.antlers.html'), '');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        URL::enforceTrailingSlashes(false);
        URL::clearUrlCache();

        $this->files->deleteDirectory(resource_path('views/topics'));
    }

    public static function trailingSlashProvider()
    {
        return [
            'without trailing slashes (default)' => [
                fn () => null,
                fn ($expected) => $expected,
            ],
            'with trailing slashes' => [
                fn () => URL::enforceTrailingSlashes(true),
                function ($expected) {
                    if (is_array($expected)) {
                        return array_map(function ($line) {
                            return str($line)
                                ->replace('</loc>', '/</loc>')
                                ->replaceMatches('/<xhtml:link rel="alternate" hreflang="([^"]+)" href="([^"]+)" \/>/', function ($matches) {
                                    return "<xhtml:link rel=\"alternate\" hreflang=\"{$matches[1]}\" href=\"{$matches[2]}/\" />";
                                })
                                ->__toString();
                        }, $expected);
                    }

                    return str_replace('</loc>', '/</loc>', $expected);
                },
            ],
        ];
    }

    #[Test]
    #[DataProvider('trailingSlashProvider')]
    public function it_outputs_italian_sitemap_xml($setupTrailingSlashes, $processExpected)
    {
        $setupTrailingSlashes();

        $this
            ->get('http://corse-fantastiche.it/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->assertSeeInOrder($processExpected([
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
            ]), escape: false)
            ->assertDontSee('<loc>http://cool-runnings.com</loc>', escape: false)
            ->assertDontSee('<loc>http://cool-runnings.com/articles/topics/sneakers</loc>', escape: false);
    }

    #[Test]
    #[DataProvider('trailingSlashProvider')]
    public function it_uses_localized_site_defaults_in_sitemap_xml($setupTrailingSlashes, $processExpected)
    {
        // There are multiple sites on the cool-runnings.com domain.
        // Pages from each site should return a different "changefreq" and "priority" (as per their site defaults).

        $setupTrailingSlashes();

        SiteDefaults::in('default')->set('change_frequency', 'monthly')->set('priority', 0.2)->save();
        SiteDefaults::in('french')->set('change_frequency', 'weekly')->set('priority', 0.4)->save();
        SiteDefaults::in('british')->set('change_frequency', 'daily')->set('priority', 1)->save();

        $content = $this
            ->get('http://cool-runnings.com/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->assertSeeInOrder($processExpected([
                '<?xml version="1.0" encoding="UTF-8"?>',
                '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">',
                '<url>',
                '<loc>http://cool-runnings.com</loc>',
                '<lastmod>2020-11-24</lastmod>',
                '<changefreq>monthly</changefreq>',
                '<priority>0.2</priority>',
                '</url>',
                '<url>',
                '<loc>http://cool-runnings.com/en-gb</loc>',
                '<lastmod>2021-09-20</lastmod>',
                '<changefreq>daily</changefreq>',
                '<priority>1</priority>',
                '</url>',
                '<url>',
                '<loc>http://cool-runnings.com/fr</loc>',
                '<lastmod>2021-09-20</lastmod>',
                '<changefreq>weekly</changefreq>',
                '<priority>0.4</priority>',
                '</url>',
            ]), escape: false)
            ->getContent();

        $this->assertCount(15, $this->getPagesFromSitemapXml($content));
    }

    protected function getPagesFromSitemapXml($content)
    {
        $data = json_decode(json_encode(simplexml_load_string($content)), true);

        return collect($data['url']);
    }
}
