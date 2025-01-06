<?php

namespace Tests;

use Statamic\Facades\Config;

class HumansTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if ($this->files->exists($folder = resource_path('views/vendor/seo-pro'))) {
            $this->files->deleteDirectory($folder);
        }

        $this->files->makeDirectory($folder, 0755, true);
    }

    /** @test */
    public function it_outputs_humans_txt()
    {
        $content = $this
            ->get('/humans.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        $expected = <<<'EOT'
/* TEAM */

Creator: Site Name
URL: http://cool-runnings.com
Description: I see a bad-ass mother.

/* THANKS */

Statamic: https://statamic.com

/* SITE */

Standards: HTML5, CSS3
Components: Statamic, Laravel, PHP, JavaScript

EOT;

        $this->assertEquals($expected, $content);
    }

    /** @test */
    public function it_404s_when_humans_txt_is_disabled()
    {
        Config::set('statamic.seo-pro.humans.enabled', false);

        $content = $this
            ->get('/humans.txt')
            ->assertStatus(404);
    }

    /** @test */
    public function it_outputs_humans_txt_with_altered_site_defaults()
    {
        $this->setSeoInSiteDefaults([
            'site_name' => 'Cool Runnings',
            'description' => 'Bob sled team!',
        ]);

        $content = $this
            ->get('/humans.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsStringIgnoringLineEndings('Creator: Cool Runnings', $content);
        $this->assertStringContainsStringIgnoringLineEndings('Description: Bob sled team!', $content);
    }

    /**
     * @test
     *
     * @environment-setup setCustomHumansTxtUrl
     */
    public function it_outputs_humans_txt_with_custom_url()
    {
        $this->setSeoInSiteDefaults([
            'site_name' => 'Cool Runnings',
        ]);

        $this
            ->get('/humans.txt')
            ->assertStatus(404);

        $content = $this
            ->get('/aliens.md')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsStringIgnoringLineEndings('Creator: Cool Runnings', $content);
    }

    /** @test */
    public function it_outputs_humans_txt_with_custom_view()
    {
        $this->setSeoInSiteDefaults([
            'site_name' => 'Cool Runnings',
        ]);

        $this->files->put(resource_path('views/vendor/seo-pro/humans.antlers.html'), 'Nice view, {{ site_name }}!');

        $content = $this
            ->get('/humans.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->getContent();

        $this->assertEquals('Nice view, Cool Runnings!', $content);
    }

    protected function setCustomHumansTxtUrl($app)
    {
        $app->config->set('statamic.seo-pro.humans', [
            'enabled' => true,
            'url' => 'aliens.md',
        ]);
    }
}
