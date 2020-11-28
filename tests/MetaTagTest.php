<?php

namespace Tests;

use Statamic\Facades\Antlers;
use Statamic\Facades\Collection;
use Statamic\Facades\Config;
use Statamic\Facades\Data;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Support\Str;
use Statamic\View\Cascade;

class MetaTagTest extends TestCase
{
    private function meta($uri = null)
    {
        $site = Site::current();
        $data = Data::findByUri(Str::ensureLeft($uri, '/'), $site->handle());
        $context = (new Cascade(request(), $site))->withContent($data)->hydrate()->toArray();

        return (string) Antlers::parse('{{ seo_pro:meta }}', $context);
    }

    /** @test */
    public function it_generates_normalized_meta()
    {
        $expected = <<<'EOT'
<title>Home | Site Name</title>
<meta name="description" content="I see a bad-ass mother." />
<meta property="og:type" content="website" />
<meta property="og:title" content="Home" />
<meta property="og:description" content="I see a bad-ass mother." />
<meta property="og:url" content="http://cool-runnings.com" />
<meta property="og:site_name" content="Site Name" />
<meta property="og:locale" content="default" />
<meta name="twitter:card" content="summary" />
<meta name="twitter:title" content="Home" />
<meta name="twitter:description" content="I see a bad-ass mother." />
<link href="http://cool-runnings.com/" rel="home" />
<link href="http://cool-runnings.com" rel="canonical" />
<link type="text/plain" rel="author" href="http://cool-runnings.com/humans.txt" />
EOT;

        $this->assertEquals($expected, $this->meta());
    }

    /** @test */
    public function it_doesnt_generate_meta_when_seo_is_disabled_on_collection()
    {
        $this->setSeoOnCollection(Collection::find('pages'), false);

        $this->assertEmpty($this->meta('about'));
    }

    /** @test */
    public function it_doesnt_generate_meta_when_seo_is_disabled_on_entry()
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), false);

        $this->assertEmpty($this->meta('about'));
    }

    /** @test */
    public function it_generates_compiled_title_meta()
    {
        $this->setSeoOnCollection(Collection::find('pages'), [
            'title' => 'Aboot',
            'site_name_position' => 'before',
            'site_name_separator' => '>>>',
        ]);

        $meta = $this->meta('/about');

        $this->assertStringContainsString('<title>Site Name &gt;&gt;&gt; Aboot</title>', $meta);
    }

    /** @test */
    public function it_uses_cascade_to_generate_meta()
    {
        $this
            ->setSeoInSiteDefaults([
                'site_name' => 'Cool Runnings',
            ])
            ->setSeoOnCollection(Collection::find('pages'), [
                'title' => 'Aboot',
                'site_name_separator' => '>',
            ])
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'site_name_position' => 'before',
                'site_name_separator' => '--',
            ]);

        $this->assertStringContainsString('<title>Cool Runnings -- Aboot</title>', $this->meta('/about'));
    }

    /** @test */
    public function it_generates_sanitized_title()
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'title' => "  It's a me, <b>Mario</b>!  ",
            'site_name' => '  Cool "Runnings"  ',
            'site_name_position' => 'before',
            'site_name_separator' => '  >>>  ',
        ]);

        $meta = $this->meta('/about');

        $this->assertStringContainsString('<title>Cool &quot;Runnings&quot; &gt;&gt;&gt; It&#039;s a me, Mario!</title>', $meta);
        $this->assertStringContainsString('<meta property="og:title" content="It&#039;s a me, Mario!" />', $meta);
    }

    /** @test */
    public function it_generates_sanitized_description()
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'description' => "  It's a me, <b>Mario</b>!  ",
        ]);

        $meta = $this->meta('/about');

        $this->assertStringContainsString('<meta name="description" content="It&#039;s a me, Mario!" />', $meta);
        $this->assertStringContainsString('<meta property="og:description" content="It&#039;s a me, Mario!" />', $meta);
    }

    /** @test */
    public function it_generates_twitter_handle_meta()
    {
        $this
            ->setSeoInSiteDefaults([
                'twitter_handle' => '  itsmario85  ',
            ])
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'twitter_handle' => '@itsluigi85',
            ]);

        $this->assertStringContainsString('<meta name="twitter:site" content="@itsmario85" />', $this->meta('/'));
        $this->assertStringContainsString('<meta name="twitter:site" content="@itsluigi85" />', $this->meta('/about'));
    }

    /** @test */
    public function it_generates_social_image()
    {
        Config::set('statamic.seo-pro.assets.container', 'assets');

        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'image' => 'img/stetson.jpg',
        ]);

        $meta = $this->meta('/about');

        $this->assertStringContainsString(
            '<meta property="og:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo&s=32e4e5b80785827c55bb5af5240e6638" />',
            $meta
        );

        $this->assertStringContainsString('<meta property="og:image:width" content="1200" />', $meta);
        $this->assertStringContainsString('<meta property="og:image:height" content="1200" />', $meta);
    }

    /**
     * @test
     * @environment-setup setCustomGlidePresetDimensions
     */
    public function it_generates_social_image_with_custom_glide_preset()
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'image' => 'img/stetson.jpg',
        ]);

        $meta = $this->meta('/about');

        $this->assertStringContainsString(
            '<meta property="og:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo&s=32e4e5b80785827c55bb5af5240e6638" />',
            $meta
        );

        $this->assertStringContainsString('<meta property="og:image:width" content="800" />', $meta);
        $this->assertStringContainsString('<meta property="og:image:height" content="600" />', $meta);
    }

    /** @test */
    public function it_generates_social_image_with_glide_preset_disabled()
    {
        Config::set('statamic.seo-pro.assets.container', 'assets');
        Config::set('statamic.seo-pro.assets.open_graph_preset', false);

        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'image' => 'img/stetson.jpg',
        ]);

        $meta = $this->meta('/about');

        $this->assertStringContainsString(
            '<meta property="og:image" content="http://cool-runnings.com/assets/img/stetson.jpg" />',
            $meta
        );

        $this->assertStringContainsString('<meta property="og:image:width" content="900" />', $meta);
        $this->assertStringContainsString('<meta property="og:image:height" content="587" />', $meta);
    }

    /** @test */
    public function it_generates_home_url_for_entry_meta()
    {
        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/" rel="home" />',
            $this->meta('/about')
        );
    }

    /** @test */
    public function it_generates_canonical_url_for_entry_meta()
    {
        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about" rel="canonical" />',
            $this->meta('/about')
        );
    }

    /** @test */
    public function it_generates_canonical_url_for_term_meta()
    {
        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/topics/sneakers" rel="canonical" />',
            $this->meta('/topics/sneakers')
        );
    }

    /** @test */
    public function it_generates_canonical_url_meta_with_pagination()
    {
        $this->call('GET', '/about', ['page' => 2]);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about?page=2" rel="canonical" />',
            $this->meta('/about')
        );
    }

    /** @test */
    public function it_generates_canonical_url_meta_with_disabled_pagination()
    {
        Config::set('statamic.seo-pro.pagination.enabled_in_canonical_url', false);

        $this->call('GET', '/about', ['page' => 2]);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about" rel="canonical" />',
            $this->meta('/about')
        );
    }

    /** @test */
    public function it_generates_canonical_url_meta_with_custom_url()
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'canonical_url' => 'https://hot-walkings.com/pages/aboot',
        ]);

        $this->assertStringContainsString(
            '<link href="https://hot-walkings.com/pages/aboot" rel="canonical" />',
            $this->meta('/about')
        );
    }

    /** @test */
    public function it_applies_pagination_to_custom_canonical_url_on_same_domain()
    {
        $this->call('GET', '/about', ['page' => 2]);

        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'canonical_url' => 'http://cool-runnings.com/pages/aboot',
        ]);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/pages/aboot?page=2" rel="canonical" />',
            $this->meta('/about')
        );
    }

    /** @test */
    public function it_doesnt_apply_pagination_to_external_custom_canonical_url()
    {
        $this->call('GET', '/about', ['page' => 2]);

        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'canonical_url' => 'https://hot-walkings.com/pages/aboot',
        ]);

        $this->assertStringContainsString(
            '<link href="https://hot-walkings.com/pages/aboot" rel="canonical" />',
            $this->meta('/about')
        );
    }

    /** @test */
    public function it_doesnt_apply_pagination_to_first_page()
    {
        $this->call('GET', '/about', ['page' => 1]);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about" rel="canonical" />',
            $this->meta('/about')
        );
    }

    /** @test */
    public function it_can_apply_pagination_to_first_page_when_configured_as_unique_page()
    {
        Config::set('statamic.seo-pro.pagination.enabled_on_first_page', true);

        $this->call('GET', '/about', ['page' => 1]);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about?page=1" rel="canonical" />',
            $this->meta('/about')
        );
    }

    /** @test */
    public function it_generates_alternate_locales_meta()
    {
        // TODO
        $this->markTestSkipped();
    }

    /** @test */
    public function it_generates_robots_meta()
    {
        $this->setSeoInSiteDefaults([
            'robots' => [
                'noindex',
            ],
        ]);

        $this->assertStringContainsString(
            '<meta name="robots" content="noindex" />',
            $meta = $this->meta('/about')
        );

        $this->setSeoInSiteDefaults([
            'robots' => [
                'noindex',
                'nofollow',
            ],
        ]);

        $this->assertStringContainsString(
            '<meta name="robots" content="noindex, nofollow" />',
            $meta = $this->meta('/about')
        );
    }

    /** @test */
    public function it_generates_custom_humans_url()
    {
        Config::set('statamic.seo-pro.humans.url', 'aliens.md');

        $this->assertStringContainsString(
            '<link type="text/plain" rel="author" href="http://cool-runnings.com/aliens.md" />',
            $meta = $this->meta('/about')
        );
    }

    /** @test */
    public function it_generates_search_engine_verification_codes()
    {
        $this->setSeoInSiteDefaults([
            'google_verification' => 'google123',
            'bing_verification' => 'bing123',
        ]);

        $meta = $this->meta('/about');

        $this->assertStringContainsString(
            '<meta name="google-site-verification" content="google123" />',
            $meta
        );

        $this->assertStringContainsString(
            '<meta name="msvalidate.01" content="bing123" />',
            $meta
        );
    }

    protected function setCustomGlidePresetDimensions($app)
    {
        $app->config->set('statamic.seo-pro.assets', [
            'container' => 'assets',
            'open_graph_preset' => [
                'w' => 800,
                'h' => 600,
            ],
        ]);
    }
}
