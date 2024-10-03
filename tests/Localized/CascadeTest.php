<?php

namespace Tests\Localized;

use Statamic\Facades\Entry;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\SiteDefaults;
use Tests\TestCase;

class CascadeTest extends TestCase
{
    protected $siteFixturePath = __DIR__.'/../Fixtures/site-localized';

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.editions.pro', true);
        $app['config']->set('statamic.system.multisite', true);
    }

    /** @test */
    public function it_generates_seo_cascade_for_canonical_url_and_alternate_locales()
    {
        $entry = Entry::findByUri('/about', 'italian')->entry();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('http://cool-runnings.com/it/about', $data['canonical_url']);
        $this->assertEquals('it', $data['current_hreflang']);

        $this->assertEquals([
            'en' => 'http://cool-runnings.com/about',
            'fr' => 'http://cool-runnings.com/fr/about',
        ], collect($data['alternate_locales'])->pluck('url', 'hreflang')->all());
    }

    /** @test */
    public function it_generates_seo_cascade_for_canonical_url_and_handles_duplicate_alternate_hreflangs()
    {
        $entry = Entry::findByUri('/', 'italian')->entry();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('http://cool-runnings.com/it', $data['canonical_url']);
        $this->assertEquals('it', $data['current_hreflang']);

        $this->assertEquals([
            'en-us' => 'http://cool-runnings.com',
            'en-gb' => 'http://cool-runnings.com/en-gb',
            'fr' => 'http://cool-runnings.com/fr',
        ], collect($data['alternate_locales'])->pluck('url', 'hreflang')->all());
    }

    /** @test */
    public function it_generates_seo_cascade_for_canonical_url_and_handles_duplicate_current_hreflang()
    {
        $entry = Entry::findByUri('/', 'default')->entry();

        $data = (new Cascade)
            ->with(SiteDefaults::load()->all())
            ->withCurrent($entry)
            ->get();

        $this->assertEquals('http://cool-runnings.com', $data['canonical_url']);
        $this->assertEquals('en-us', $data['current_hreflang']);

        $this->assertEquals([
            'en-gb' => 'http://cool-runnings.com/en-gb',
            'fr' => 'http://cool-runnings.com/fr',
            'it' => 'http://cool-runnings.com/it',
        ], collect($data['alternate_locales'])->pluck('url', 'hreflang')->all());
    }
}
