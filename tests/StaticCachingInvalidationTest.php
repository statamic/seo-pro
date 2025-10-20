<?php

namespace Tests;

use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;
use Statamic\StaticCaching\Cacher;

class StaticCachingInvalidationTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.static_caching.strategy', 'full');
    }

    #[Test]
    public function does_nothing_when_static_caching_is_disabled()
    {
        $cacher = Mockery::mock(Cacher::class);
        $cacher->shouldReceive('flush')->never();
        $cacher->shouldReceive('invalidateUrls')->never();

        $this->app->bind(Cacher::class, fn () => $cacher);

        config()->set('statamic.static_caching.strategy', null);
        config()->set('statamic.static_caching.invalidation.rules', 'all');

        SiteDefaults::get()->first()->save();
    }

    #[Test]
    public function flushes_static_cache_when_site_defaults_are_saved()
    {
        $cacher = Mockery::mock(Cacher::class)->shouldReceive('flush')->once()->getMock();

        $this->app->bind(Cacher::class, fn () => $cacher);

        config()->set('statamic.static_caching.invalidation.rules', 'all');

        SiteDefaults::get()->first()->save();
    }

    #[Test]
    public function invalidates_urls_when_site_defaults_are_saved()
    {
        $cacher = Mockery::mock(Cacher::class)->shouldReceive('invalidateUrls')->with([
            '/foo',
            '/bar',
        ])->once()->getMock();

        $this->app->bind(Cacher::class, fn () => $cacher);

        config()->set('statamic.static_caching.invalidation.rules', [
            'seo_pro_site_defaults' => [
                'urls' => [
                    '/foo',
                    '/bar',
                ],
            ],
        ]);

        SiteDefaults::get()->first()->save();
    }
}
