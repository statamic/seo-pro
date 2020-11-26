<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Statamic\Extend\Manifest;
use Statamic\SeoPro\SiteDefaults;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Statamic\Providers\StatamicServiceProvider::class,
            \Statamic\SeoPro\ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return ['Statamic' => 'Statamic\Statamic'];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);

        $this->files->copyDirectory(__DIR__.'/Fixtures/content', base_path('content'));
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));

        $this->restoreStatamicConfigs();
        $this->restoreSiteDefaults();
    }

    protected function tearDown(): void
    {
        $this->restoreStatamicConfigs();
        $this->restoreSiteDefaults();

        parent::tearDown();
    }

    protected function restoreStatamicConfigs()
    {
        $this->files->copyDirectory(__DIR__.'/../vendor/statamic/cms/config', config_path('statamic'));

        $configs = [
            'filesystems',
            'statamic/users',
            'statamic/stache',
        ];

        foreach ($configs as $config) {
            $this->files->delete(config_path("{$config}.php"));
            $this->files->copy(__DIR__."/Fixtures/config/{$config}.php", config_path("{$config}.php"));
        }
    }

    protected function restoreSiteDefaults()
    {
        if ($this->files->exists($path = base_path('content/seo.yaml'))) {
            $this->files->delete($path);
        }
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $configs = [
            'assets', 'cp', 'forms', 'routes', 'static_caching',
            'sites', 'stache', 'system', 'users',
        ];

        foreach ($configs as $config) {
            $app['config']->set("statamic.$config", require(config_path("statamic/{$config}.php")));
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->make(Manifest::class)->manifest = [
            'statamic/seo-pro' => [
                'id' => 'statamic/seo-pro',
                'namespace' => 'Statamic\\SeoPro\\',
            ],
        ];
    }

    protected function setSeoInSiteDefaults($seo)
    {
        SiteDefaults::load($seo)->save();

        return $this;
    }

    protected function setSeoOnCollection($collection, $seo)
    {
        $collection->cascade(['seo' => $seo])->save();

        return $this;
    }

    protected function setSeoOnEntry($entry, $seo)
    {
        $entry->data(['seo' => $seo])->save();

        return $this;
    }

    protected static function normalizeMultilineString($string)
    {
        return str_replace("\r\n", "\n", $string);
    }

    /**
     * Normalize line endings before performing assertion in windows.
     */
    public static function assertStringContainsString($needle, $haystack, $message = '') : void
    {
        parent::assertStringContainsString(
            static::normalizeMultilineString($needle),
            static::normalizeMultilineString($haystack),
            $message
        );
    }

    /**
     * @deprecated
     */
    public static function assertFileNotExists(string $filename, string $message = '') : void
    {
        method_exists(static::class, 'assertFileDoesNotExist')
            ? static::assertFileDoesNotExist($filename, $message)
            : parent::assertFileNotExists($filename, $message);
    }
}
