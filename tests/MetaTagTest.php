<?php

namespace Tests;

use Illuminate\Pagination\LengthAwarePaginator;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;

class MetaTagTest extends TestCase
{
    use MetaProviders;

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_normalized_meta($metaProvider)
    {
        $expected = <<<'EOT'
<title>Home | Site Name</title>
<meta name="description" content="I see a bad-ass mother." />
<meta property="og:type" content="website" />
<meta property="og:title" content="Home" />
<meta property="og:description" content="I see a bad-ass mother." />
<meta property="og:url" content="http://cool-runnings.com" />
<meta property="og:site_name" content="Site Name" />
<meta property="og:locale" content="en_US" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="Home" />
<meta name="twitter:description" content="I see a bad-ass mother." />
<link href="http://cool-runnings.com/" rel="home" />
<link href="http://cool-runnings.com" rel="canonical" />
<link type="text/plain" rel="author" href="http://cool-runnings.com/humans.txt" />
EOT;

        $this->assertEquals($this->normalizeMultilineString($expected), $this->{$metaProvider}());
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_doesnt_generate_meta_when_seo_is_disabled_on_collection($metaProvider)
    {
        $this->setSeoOnCollection(Collection::find('pages'), false);

        $this->assertEmpty($this->{$metaProvider}('about'));
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_doesnt_generate_meta_when_seo_is_disabled_on_entry($metaProvider)
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), false);

        $this->assertEmpty($this->{$metaProvider}('about'));
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_compiled_title_meta($metaProvider)
    {
        $this->setSeoOnCollection(Collection::find('pages'), [
            'title' => 'Aboot',
            'site_name_position' => 'before',
            'site_name_separator' => '>>>',
        ]);

        $meta = $this->{$metaProvider}('/about');

        $this->assertStringContainsString('<title>Site Name &gt;&gt;&gt; Aboot</title>', $meta);
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_uses_cascade_to_generate_meta($metaProvider)
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

        $this->assertStringContainsString('<title>Cool Runnings -- Aboot</title>', $this->{$metaProvider}('/about'));
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_sanitized_title($metaProvider)
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'title' => "  It's a me, <b>Mario</b>!  ",
            'site_name' => '  Cool "Runnings"  ',
            'site_name_position' => 'before',
            'site_name_separator' => '  >>>  ',
        ]);

        $meta = $this->{$metaProvider}('/about');

        $this->assertStringContainsString('<title>Cool &quot;Runnings&quot; &gt;&gt;&gt; It&#039;s a me, Mario!</title>', $meta);
        $this->assertStringContainsString('<meta property="og:title" content="It&#039;s a me, Mario!" />', $meta);
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_sanitized_description($metaProvider)
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'description' => "  It's a me, <b>Mario</b>!  ",
        ]);

        $meta = $this->{$metaProvider}('/about');

        $this->assertStringContainsString('<meta name="description" content="It&#039;s a me, Mario!" />', $meta);
        $this->assertStringContainsString('<meta property="og:description" content="It&#039;s a me, Mario!" />', $meta);
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_custom_twitter_card_with_short_summary($metaProvider)
    {
        Config::set('statamic.seo-pro.twitter.card', 'summary');

        $this->assertStringContainsString('<meta name="twitter:card" content="summary" />', $this->{$metaProvider}('/'));
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_twitter_handle_meta($metaProvider)
    {
        $this
            ->setSeoInSiteDefaults([
                'twitter_handle' => '  itsmario85  ',
            ])
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'twitter_handle' => '@itsluigi85',
            ]);

        $this->assertStringContainsString('<meta name="twitter:site" content="@itsmario85" />', $this->{$metaProvider}('/'));
        $this->assertStringContainsString('<meta name="twitter:site" content="@itsluigi85" />', $this->{$metaProvider}('/about'));
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_social_image($metaProvider)
    {
        Config::set('statamic.seo-pro.assets.container', 'assets');

        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'image' => 'img/stetson.jpg',
        ]);

        $meta = $this->{$metaProvider}('/about');

        $this->assertStringContainsString(
            '<meta property="og:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo_pro_og&s=6e3bd8a29425c3b1fcc63d3c0ed02ff8" />',
            $meta
        );

        $this->assertStringContainsString('<meta property="og:image:width" content="1146" />', $meta);
        $this->assertStringContainsString('<meta property="og:image:height" content="600" />', $meta);

        $this->assertStringContainsString(
            '<meta name="twitter:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo_pro_twitter&s=3bc5d83bf276b3825695610a6ef88d5b" />',
            $meta
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     * @environment-setup setCustomGlidePresetDimensions
     */
    public function it_generates_social_image_with_custom_glide_presets($metaProvider)
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'image' => 'img/stetson.jpg',
        ]);

        $meta = $this->{$metaProvider}('/about');

        $this->assertStringContainsString(
            '<meta property="og:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo_pro_og&s=6e3bd8a29425c3b1fcc63d3c0ed02ff8" />',
            $meta
        );

        $this->assertStringContainsString('<meta property="og:image:width" content="800" />', $meta);
        $this->assertStringContainsString('<meta property="og:image:height" content="600" />', $meta);
    }

    /**
     * @test
     * @dataProvider metaProvider
     * @environment-setup setCustomOgGlidePresetOnly
     */
    public function it_generates_social_image_with_og_glide_preset_only($metaProvider)
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'image' => 'img/stetson.jpg',
        ]);

        $meta = $this->{$metaProvider}('/about');

        $this->assertStringContainsString(
            '<meta property="og:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo_pro_og&s=6e3bd8a29425c3b1fcc63d3c0ed02ff8" />',
            $meta
        );

        $this->assertStringContainsString(
            '<meta name="twitter:image" content="http://cool-runnings.com/assets/img/stetson.jpg" />',
            $meta
        );

        $this->assertStringContainsString('<meta property="og:image:width" content="800" />', $meta);
        $this->assertStringContainsString('<meta property="og:image:height" content="600" />', $meta);
    }

    /**
     * @test
     * @dataProvider metaProvider
     * @environment-setup setCustomTwitterGlidePresetOnly
     */
    public function it_generates_social_image_with_twitter_glide_preset_only($metaProvider)
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'image' => 'img/stetson.jpg',
        ]);

        $meta = $this->{$metaProvider}('/about');

        $this->assertStringContainsString(
            '<meta property="og:image" content="http://cool-runnings.com/assets/img/stetson.jpg" />',
            $meta
        );

        $this->assertStringContainsString(
            '<meta name="twitter:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo_pro_twitter&s=3bc5d83bf276b3825695610a6ef88d5b" />',
            $meta
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_home_url_for_entry_meta($metaProvider)
    {
        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/" rel="home" />',
            $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_canonical_url_for_entry_meta($metaProvider)
    {
        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about" rel="canonical" />',
            $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_canonical_url_for_term_meta($metaProvider)
    {
        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/topics/sneakers" rel="canonical" />',
            $this->{$metaProvider}('/topics/sneakers')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_canonical_url_meta_with_pagination($metaProvider)
    {
        $this->simulatePageOutOfFive(2);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about?page=2" rel="canonical" />',
            $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_canonical_url_meta_without_pagination($metaProvider)
    {
        Config::set('statamic.seo-pro.pagination.enabled_in_canonical_url', false);

        $this->simulatePageOutOfFive(2);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about" rel="canonical" />',
            $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_rel_next_prev_url_meta($metaProvider)
    {
        $this->simulatePageOutOfFive(1);

        $meta = $this->{$metaProvider}('/about');
        $this->assertStringNotContainsString('rel="prev"', $meta);
        $this->assertStringContainsString('<link href="http://cool-runnings.com/about?page=2" rel="next" />', $meta);

        $this->simulatePageOutOfFive(2);

        $meta = $this->{$metaProvider}('/about');
        $this->assertStringContainsString('<link href="http://cool-runnings.com/about" rel="prev" />', $meta);
        $this->assertStringContainsString('<link href="http://cool-runnings.com/about?page=3" rel="next" />', $meta);

        $this->simulatePageOutOfFive(3);

        $meta = $this->{$metaProvider}('/about');
        $this->assertStringContainsString('<link href="http://cool-runnings.com/about?page=2" rel="prev" />', $meta);
        $this->assertStringContainsString('<link href="http://cool-runnings.com/about?page=4" rel="next" />', $meta);

        $this->simulatePageOutOfFive(5);

        $meta = $this->{$metaProvider}('/about');
        $this->assertStringContainsString('<link href="http://cool-runnings.com/about?page=4" rel="prev" />', $meta);
        $this->assertStringNotContainsString('rel="next"', $meta);
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_rel_next_prev_url_meta_with_first_page_enabled($metaProvider)
    {
        Config::set('statamic.seo-pro.pagination.enabled_on_first_page', true);

        $this->simulatePageOutOfFive(2);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about?page=1" rel="prev" />',
            $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_doesnt_generate_rel_next_prev_url_meta_without_paginator($metaProvider)
    {
        $meta = $this->{$metaProvider}('/about');

        $this->assertStringNotContainsString('rel="next"', $meta);
        $this->assertStringNotContainsString('rel="prev"', $meta);
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_doesnt_generate_any_pagination_when_completely_disabled($metaProvider)
    {
        Config::set('statamic.seo-pro.pagination', false);

        $this->simulatePageOutOfFive(2);

        $meta = $this->{$metaProvider}('/about');

        $this->assertStringNotContainsString('page=2', $meta);
        $this->assertStringNotContainsString('rel="next"', $meta);
        $this->assertStringNotContainsString('rel="prev"', $meta);
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_canonical_url_meta_with_custom_url($metaProvider)
    {
        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'canonical_url' => 'https://hot-walkings.com/pages/aboot',
        ]);

        $this->assertStringContainsString(
            '<link href="https://hot-walkings.com/pages/aboot" rel="canonical" />',
            $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_applies_pagination_to_custom_canonical_url_on_same_domain($metaProvider)
    {
        $this->simulatePageOutOfFive(2);

        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'canonical_url' => 'http://cool-runnings.com/pages/aboot',
        ]);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/pages/aboot?page=2" rel="canonical" />',
            $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_doesnt_apply_pagination_to_external_custom_canonical_url($metaProvider)
    {
        $this->simulatePageOutOfFive(2);

        $this->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
            'canonical_url' => 'https://hot-walkings.com/pages/aboot',
        ]);

        $this->assertStringContainsString(
            '<link href="https://hot-walkings.com/pages/aboot" rel="canonical" />',
            $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_doesnt_apply_pagination_to_first_page($metaProvider)
    {
        $this->simulatePageOutOfFive(1);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about" rel="canonical" />',
            $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_can_apply_pagination_to_first_page_when_configured_as_unique_page($metaProvider)
    {
        Config::set('statamic.seo-pro.pagination.enabled_on_first_page', true);

        $this->simulatePageOutOfFive(1);

        $this->assertStringContainsString(
            '<link href="http://cool-runnings.com/about?page=1" rel="canonical" />',
            $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_robots_meta($metaProvider)
    {
        $this->setSeoInSiteDefaults([
            'robots' => [
                'noindex',
            ],
        ]);

        $this->assertStringContainsString(
            '<meta name="robots" content="noindex" />',
            $meta = $this->{$metaProvider}('/about')
        );

        $this->setSeoInSiteDefaults([
            'robots' => [
                'noindex',
                'nofollow',
            ],
        ]);

        $this->assertStringContainsString(
            '<meta name="robots" content="noindex, nofollow" />',
            $meta = $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_custom_humans_url($metaProvider)
    {
        Config::set('statamic.seo-pro.humans.url', 'aliens.md');

        $this->assertStringContainsString(
            '<link type="text/plain" rel="author" href="http://cool-runnings.com/aliens.md" />',
            $meta = $this->{$metaProvider}('/about')
        );
    }

    /**
     * @test
     * @dataProvider metaProvider
     */
    public function it_generates_search_engine_verification_codes($metaProvider)
    {
        $this->setSeoInSiteDefaults([
            'google_verification' => 'google123',
            'bing_verification' => 'bing123',
        ]);

        $meta = $this->{$metaProvider}('/about');

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
            'twitter_preset' => [
                'w' => 1024,
                'h' => 768,
            ],
            'open_graph_preset' => [
                'w' => 800,
                'h' => 600,
            ],
        ]);
    }

    protected function setCustomOgGlidePresetOnly($app)
    {
        $app->config->set('statamic.seo-pro.assets', [
            'container' => 'assets',
            'twitter_preset' => false,
            'open_graph_preset' => [
                'w' => 800,
                'h' => 600,
            ],
        ]);
    }

    protected function setCustomTwitterGlidePresetOnly($app)
    {
        $app->config->set('statamic.seo-pro.assets', [
            'container' => 'assets',
            'twitter_preset' => [
                'w' => 800,
                'h' => 600,
            ],
            'open_graph_preset' => false,
        ]);
    }

    protected function simulatePageOutOfFive($currentPage)
    {
        Blink::put('tag-paginator', new LengthAwarePaginator([], 15, 3, $currentPage));

        $this->call('GET', '/about', ['page' => $currentPage]);
    }
}
