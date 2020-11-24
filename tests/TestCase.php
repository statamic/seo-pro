<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;

class TestCase extends \Orchestra\Testbench\TestCase
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

        $this->restoreStatamicConfigs();
    }

    protected function tearDown(): void
    {
        $this->restoreStatamicConfigs();

        parent::tearDown();
    }

    protected function restoreStatamicConfigs()
    {
        $this->files->copyDirectory(__DIR__.'/../vendor/statamic/cms/config', config_path('statamic'));
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
