<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\SeoPro\Models\SeoDefaults;
use Statamic\SeoPro\SiteDefaults;

class SiteDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     *
     * @environment-setup setEloquentSiteDefaults
     */
    public function it_uses_eloquent_defaults_when_requested()
    {
        $this->assertInstanceOf(\Statamic\SeoPro\Eloquent\SiteDefaults::class, app(SiteDefaults::class));

        $this->assertCount(1, SeoDefaults::all());
    }

    protected function setEloquentSiteDefaults($app)
    {
        $app->config->set('statamic.seo-pro.site_defaults.driver', 'eloquent');
    }
}
