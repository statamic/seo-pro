<?php

namespace Tests\Localized;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Http\Controllers\LlmsController;
use Statamic\SeoPro\Llms\Llms;
use Statamic\SeoPro\Llms\LlmsDocument;
use Statamic\SeoPro\Llms\LlmsRoutes;
use Statamic\SeoPro\Llms\LlmsTxtGenerator;

class LlmsTest extends LocalizedTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        LlmsRoutes::register(Site::all());
    }

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
    public function routes_support_deeply_nested_site_paths()
    {
        $sites = YAML::file("{$this->siteFixturePath}/resources/sites.yaml")->parse();
        $sites['german'] = [
            'name' => 'German',
            'locale' => 'de_DE',
            'url' => 'http://cool-runnings.com/regions/europe/de/',
        ];

        Site::setSites($sites);
        URL::clearUrlCache();
        LlmsRoutes::register(Site::all());

        Llms::saveWithoutGenerated(new LlmsDocument([
            'enabled' => true,
            'title' => 'Deutsch',
        ]), Site::get('german'));

        $this->get('http://cool-runnings.com/regions/europe/de/llms.txt')
            ->assertOk()
            ->assertContent("# Deutsch\n");
    }

    #[Test]
    public function routes_are_scoped_to_their_site_domains()
    {
        Llms::saveWithoutGenerated(new LlmsDocument([
            'enabled' => true,
            'title' => 'English',
        ]), Site::get('default'));
        Llms::saveWithoutGenerated(new LlmsDocument([
            'enabled' => true,
            'title' => 'Italiano',
        ]), Site::get('italian'));

        $this->get('http://cool-runnings.com/llms.txt')
            ->assertOk()
            ->assertContent("# English\n");
        $this->get('http://corse-fantastiche.it/llms.txt')
            ->assertOk()
            ->assertContent("# Italiano\n");
    }

    #[Test]
    public function unrelated_llms_txt_paths_are_not_claimed_by_seo_pro()
    {
        $request = Request::create('http://cool-runnings.com/not-a-site/llms.txt');
        $route = Route::getRoutes()->match($request);

        $this->assertNotSame(LlmsController::class.'@show', $route->getActionName());
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
