<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Site;
use Statamic\SeoPro\Llms\Llms;
use Statamic\SeoPro\Llms\LlmsDocument;

class GenerateLlmsTxtCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->files->delete(public_path('llms.txt'));

        parent::tearDown();
    }

    #[Test]
    public function it_does_nothing_when_the_feature_is_disabled()
    {
        $this->artisan('statamic:seo-pro:generate:llms-txt')
            ->expectsOutputToContain('llms.txt is not enabled')
            ->assertSuccessful();

        $this->assertFileDoesNotExist(public_path('llms.txt'));
    }

    #[Test]
    public function it_generates_an_enabled_site_for_git_workflows()
    {
        Llms::saveWithoutGenerated(new LlmsDocument([
            'enabled' => true,
            'title' => 'Cool Runnings',
        ]), Site::default());

        $this->artisan('statamic:seo-pro:generate:llms-txt', ['--site' => ['default']])
            ->expectsOutputToContain('Generated llms.txt for [default].')
            ->expectsOutputToContain(public_path('llms.txt'))
            ->assertSuccessful();

        $this->assertSame("# Cool Runnings\n", $this->files->get(public_path('llms.txt')));
    }

    #[Test]
    public function it_fails_without_overwriting_an_unmanaged_file()
    {
        Llms::saveWithoutGenerated(new LlmsDocument([
            'enabled' => true,
            'title' => 'Cool Runnings',
        ]), Site::default());
        $this->files->put(public_path('llms.txt'), "# Manual\n");

        $this->artisan('statamic:seo-pro:generate:llms-txt')
            ->expectsOutputToContain('is not managed by SEO Pro')
            ->assertFailed();

        $this->assertSame("# Manual\n", $this->files->get(public_path('llms.txt')));
    }
}
