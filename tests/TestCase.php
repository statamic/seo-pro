<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Testing\TestResponse;
use Statamic\Extend\Manifest;
use Statamic\SeoPro\SiteDefaults;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $siteFixturePath = __DIR__.'/Fixtures/site';

    protected function getPackageProviders($app)
    {
        return [
            \Statamic\Providers\StatamicServiceProvider::class,
            \Statamic\SeoPro\ServiceProvider::class,
            \Rebing\GraphQL\GraphQLServiceProvider::class,
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

        $this->copyDirectoryFromFixture('content');
        $this->copyDirectoryFromFixture('assets');

        $this->restoreSiteDefaults();

        $this->addGqlMacros();
    }

    protected function copyDirectoryFromFixture($directory)
    {
        if (base_path($directory)) {
            $this->files->deleteDirectory(base_path($directory));
        }

        $this->files->copyDirectory("{$this->siteFixturePath}/{$directory}", base_path($directory));
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
            'stache', 'system', 'users',
        ];

        foreach ($configs as $config) {
            $app['config']->set("statamic.$config", require (__DIR__."/../vendor/statamic/cms/config/{$config}.php"));
        }

        $files = new Filesystem;

        $files->copyDirectory(__DIR__.'/../vendor/statamic/cms/config', config_path('statamic'));

        $configs = [
            'filesystems',
            'statamic/users',
            'statamic/stache',
            'statamic/sites',
        ];

        foreach ($configs as $config) {
            $files->delete(config_path("{$config}.php"));
            $files->copy("{$this->siteFixturePath}/config/{$config}.php", config_path("{$config}.php"));
            $app['config']->set(str_replace('/', '.', $config), require("{$this->siteFixturePath}/config/{$config}.php"));
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->make(Manifest::class)->manifest = [
            'statamic/seo-pro' => [
                'id' => 'statamic/seo-pro',
                'namespace' => 'Statamic\\SeoPro',
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
        $entry->set('seo', $seo)->save();

        return $this;
    }

    protected static function normalizeMultilineString($string)
    {
        return str_replace("\r\n", "\n", $string);
    }

    public static function assertArraySubset($subset, $array, bool $checkForObjectIdentity = false, string $message = ''): void
    {
        \Illuminate\Testing\Assert::class::assertArraySubset($subset, $array, $checkForObjectIdentity, $message);
    }

    /**
     * Normalize line endings before performing assertion in windows.
     */
    public static function assertStringContainsString($needle, $haystack, $message = ''): void
    {
        parent::assertStringContainsString(
            static::normalizeMultilineString($needle),
            static::normalizeMultilineString($haystack),
            $message
        );
    }

    /**
     * Normalize line endings before performing assertion in windows.
     */
    public static function assertStringNotContainsString($needle, $haystack, $message = ''): void
    {
        parent::assertStringNotContainsString(
            static::normalizeMultilineString($needle),
            static::normalizeMultilineString($haystack),
            $message
        );
    }

    /**
     * @deprecated
     */
    public static function assertFileNotExists(string $filename, string $message = ''): void
    {
        method_exists(static::class, 'assertFileDoesNotExist')
            ? static::assertFileDoesNotExist($filename, $message)
            : parent::assertFileNotExists($filename, $message);
    }

    private function addGqlMacros()
    {
        TestResponse::macro('assertGqlOk', function () {
            $this->assertOk();

            $json = $this->json();

            if (isset($json['errors'])) {
                throw new \PHPUnit\Framework\ExpectationFailedException(
                    'GraphQL response contained errors',
                    new \SebastianBergmann\Comparator\ComparisonFailure('', '', '', json_encode($json, JSON_PRETTY_PRINT))
                );
            }

            return $this;
        });
    }
}
