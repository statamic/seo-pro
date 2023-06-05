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

        $this->assertEquals([
            'http://cool-runnings.com/about',
            'http://cool-runnings.com/fr/about',
        ], collect($data['alternate_locales'])->pluck('url')->all());
    }
}
