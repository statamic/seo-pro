<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Site;
use Statamic\SeoPro\Llms\Llms;
use Statamic\SeoPro\Llms\LlmsDocument;
use Statamic\SeoPro\Llms\LlmsTxtGenerator;

class LlmsTest extends LocalizedTestCase
{
    protected function tearDown(): void
    {
        $this->files->delete(public_path('llms.txt'));
        $this->files->deleteDirectory(public_path('fr'));

        parent::tearDown();
    }

    #[Test]
    public function routes_and_settings_are_scoped_per_site()
    {
        Llms::saveWithoutGenerated(new LlmsDocument([
            'enabled' => true,
            'title' => 'English',
        ]), Site::get('default'));
        Llms::saveWithoutGenerated(new LlmsDocument([
            'enabled' => true,
            'title' => 'Français',
        ]), Site::get('french'));

        $this->get('http://cool-runnings.com/llms.txt')
            ->assertOk()
            ->assertContent("# English\n");
        $this->get('http://cool-runnings.com/fr/llms.txt')
            ->assertOk()
            ->assertContent("# Français\n");
        $this->get('http://cool-runnings.com/en-gb/llms.txt')->assertNotFound();
    }

    #[Test]
    public function generated_files_follow_each_sites_base_path()
    {
        $generator = app(LlmsTxtGenerator::class);
        $document = new LlmsDocument(['enabled' => true, 'title' => 'Français']);

        $result = $generator->generate($document, 'french');

        $this->assertSame(public_path('fr/llms.txt'), $result['path']);
        $this->assertSame("# Français\n", $this->files->get(public_path('fr/llms.txt')));
    }

    #[Test]
    public function selected_content_is_resolved_for_the_configured_site()
    {
        Llms::saveWithoutGenerated(new LlmsDocument([
            'enabled' => true,
            'title' => 'Français',
            'collections' => ['articles'],
            'entries' => ['62136fa2-9e5c-4c38-a894-a2753f02f5ff'],
        ]), Site::get('french'));

        $contents = $this->get('http://cool-runnings.com/fr/llms.txt')
            ->assertOk()
            ->content();

        $this->assertStringContainsString(
            '- [Les Nectar of the Gods](http://cool-runnings.com/fr/nectar)',
            $contents,
        );
        $this->assertStringContainsString(
            '- [About](http://cool-runnings.com/fr/about)',
            $contents,
        );
        $this->assertStringNotContainsString('The Magic Happens', $contents);
    }
}
