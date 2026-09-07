<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

class CascadeTest extends LocalizedTestCase
{
    #[Test]
    public function it_generates_seo_cascade_for_canonical_url_and_alternate_locales()
    {
        $entry = Entry::findByUri('/about', 'italian')->entry();

        $data = (new Cascade)
            ->withSiteDefaults(SiteDefaults::in('italian')->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('http://corse-fantastiche.it/about', $data['canonical_url']);
        $this->assertEquals('it', $data['current_hreflang']);

        $this->assertEquals([
            'en' => 'http://cool-runnings.com/about',
            'fr' => 'http://cool-runnings.com/fr/about',
        ], collect($data['alternate_locales'])->pluck('url', 'hreflang')->all());
    }

    #[Test]
    public function it_reindexes_alternate_locales_after_filtering()
    {
        Config::set('statamic.seo-pro.alternate_locales.excluded_sites', ['french']);

        $entry = Entry::findByUri('/about', 'default')->entry();

        $data = (new Cascade)
            ->withSiteDefaults(SiteDefaults::in('default')->all())
            ->withCurrent($entry)
            ->get();

        $this->assertSame([0], array_keys($data['alternate_locales']));
        $this->assertSame('it', $data['alternate_locales'][0]['hreflang']);
    }

    #[Test]
    public function it_generates_seo_cascade_for_canonical_url_and_handles_duplicate_alternate_hreflangs()
    {
        $entry = Entry::findByUri('/', 'italian')->entry();

        $data = (new Cascade)
            ->withSiteDefaults(SiteDefaults::in('italian')->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('http://corse-fantastiche.it', $data['canonical_url']);
        $this->assertEquals('it', $data['current_hreflang']);

        $this->assertEquals([
            'en-us' => 'http://cool-runnings.com',
            'en-gb' => 'http://cool-runnings.com/en-gb',
            'fr' => 'http://cool-runnings.com/fr',
        ], collect($data['alternate_locales'])->pluck('url', 'hreflang')->all());
    }

    #[Test]
    public function it_generates_seo_cascade_for_canonical_url_and_handles_duplicate_current_hreflang()
    {
        $entry = Entry::findByUri('/', 'default')->entry();

        $data = (new Cascade)
            ->withSiteDefaults(SiteDefaults::in('italian')->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('http://cool-runnings.com', $data['canonical_url']);
        $this->assertEquals('en-us', $data['current_hreflang']);

        $this->assertEquals([
            'en-gb' => 'http://cool-runnings.com/en-gb',
            'fr' => 'http://cool-runnings.com/fr',
            'it' => 'http://corse-fantastiche.it',
        ], collect($data['alternate_locales'])->pluck('url', 'hreflang')->all());
    }

    #[Test]
    public function it_generates_json_ld_breadcrumbs_for_entry_using_title_from_origin()
    {
        $siteDefaults = SiteDefaults::in('french')->set([
            'json_ld_breadcrumbs' => true,
        ]);

        $this->get('http://cool-runnings.com/fr/about');

        $data = (new Cascade)
            ->with($siteDefaults->all())
            ->get();

        $breadcrumbs = collect($data['json_ld'])->first(fn ($snippet) => str_contains($snippet, 'BreadcrumbList'));
        $breadcrumbs = json_decode($breadcrumbs, true);

        $this->assertEquals('BreadcrumbList', $breadcrumbs['@type']);

        $lastItem = end($breadcrumbs['itemListElement']);
        $this->assertEquals('About', $lastItem['name']);
        $this->assertEquals('http://cool-runnings.com/fr/about', $lastItem['item']);
    }

    #[Test]
    public function it_only_outputs_organization_schema_on_each_sites_homepage()
    {
        $siteDefaults = SiteDefaults::in('french')->set([
            'json_ld_entity' => 'organization',
            'json_ld_entity_name' => 'Cool Runnings Ltd',
        ]);

        $this->get('http://cool-runnings.com/fr');

        $home = (new Cascade)
            ->with($siteDefaults->all())
            ->get();

        $this->assertContains(
            '{"@context":"https://schema.org","@type":"Organization","name":"Cool Runnings Ltd","@id":"http://cool-runnings.com/fr#organization","url":"http://cool-runnings.com/fr"}',
            $home['json_ld']->all()
        );

        $this->get('http://cool-runnings.com/fr/about');

        $about = (new Cascade)
            ->with($siteDefaults->all())
            ->withCurrent(Entry::findByUri('/about', 'french')->entry())
            ->get();

        $this->assertEmpty($about['json_ld']->all());
    }
}
