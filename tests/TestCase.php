<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Facades\YAML;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected $siteFixturePath = __DIR__.'/Fixtures/site';
    protected $files;
    protected string $addonServiceProvider = \Statamic\SeoPro\ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);

        $this->copyDirectoryFromFixture('content');
        $this->copyDirectoryFromFixture('assets');

        Site::setSites(YAML::file("{$this->siteFixturePath}/resources/sites.yaml")->parse());

        URL::clearUrlCache();

        $this->restoreSiteDefaults();

        $this->addGqlMacros();

        $this->addTestResponseMacros();
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
        if ($this->files->exists($path = base_path('resources/addons/seo-pro.yaml'))) {
            $this->files->delete($path);
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $configs = [
            'assets', 'cp', 'forms', 'routes', 'static_caching',
            'stache', 'system', 'users',
        ];

        foreach ($configs as $config) {
            $app['config']->set("statamic.$config", require (__DIR__."/../vendor/statamic/cms/config/{$config}.php"));
        }

        $app['config']->set('app.debug', true);
        $app['config']->set('statamic.antlers.errorOnAccessViolation', true);

        $files = new Filesystem;

        $files->copyDirectory(__DIR__.'/../vendor/statamic/cms/config', config_path('statamic'));

        $configs = [
            'filesystems',
            'statamic/users',
            'statamic/stache',
        ];

        foreach ($configs as $config) {
            $files->delete(config_path("{$config}.php"));
            $files->copy("{$this->siteFixturePath}/config/{$config}.php", config_path("{$config}.php"));
            $app['config']->set(str_replace('/', '.', $config), require ("{$this->siteFixturePath}/config/{$config}.php"));
        }
    }

    protected function setSeoInSiteDefaults($seo)
    {
        $siteDefaults = SiteDefaults::in('default');

        foreach ($seo as $key => $value) {
            $siteDefaults->set($key, $value);
        }

        $siteDefaults->save();

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
    public static function assertStringNotContainsStringIgnoringLineEndings($needle, $haystack, $message = ''): void
    {
        parent::assertStringNotContainsString(
            static::normalizeMultilineString($needle),
            static::normalizeMultilineString($haystack),
            $message
        );
    }

    protected function assertArrayHasKeys(array $keys, array|\ArrayAccess $array): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array);
        }
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

    private function addTestResponseMacros()
    {
        // Symfony 7.4.0 changed "UTF-8" to "utf-8".
        // https://github.com/symfony/symfony/pull/60685
        // While we continue to support lower versions, we'll do a case-insensitive check.
        // This macro is essentially assertHeader but with case-insensitive value check.
        TestResponse::macro('assertContentType', function (string $value) {
            $headerName = 'Content-Type';

            Assert::assertTrue(
                $this->headers->has($headerName), "Header [{$headerName}] not present on response."
            );

            $actual = $this->headers->get($headerName);

            if (! is_null($value)) {
                Assert::assertEquals(
                    strtolower($value), strtolower($this->headers->get($headerName)),
                    "Header [{$headerName}] was found, but value [{$actual}] does not match [{$value}]."
                );
            }

            return $this;
        });
    }
}
