<?php

namespace Tests;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;

class MetaTagTest extends TestCase
{
    use ViewScenarios;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('view.paths', [$this->viewsPath()]);
    }

    public function tearDown(): void
    {
        $this->cleanUpViews();

        parent::tearDown();
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_normalized_meta($viewType)
    {
        $this->prepareViews($viewType);

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

        $content = $this->get('/')->content();

        $this->assertStringContainsString("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsString($this->normalizeMultilineString($expected), $content);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_generate_meta_when_seo_is_disabled_on_collection($viewType)
    {
        $this
            ->prepareViews($viewType)
            ->setSeoOnCollection(Collection::find('pages'), false);

        $response = $this->get('/');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertDontSee('<title', false);
        $response->assertDontSee('<meta', false);
        $response->assertDontSee('<link', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_generate_meta_when_seo_is_disabled_on_entry($viewType)
    {
        $this
            ->prepareViews($viewType)
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), false);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertDontSee('<title', false);
        $response->assertDontSee('<meta', false);
        $response->assertDontSee('<link', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_compiled_title_meta($viewType)
    {
        $this
            ->prepareViews($viewType)
            ->setSeoOnCollection(Collection::find('pages'), [
                'title' => 'Aboot',
                'site_name_position' => 'before',
                'site_name_separator' => '>>>',
            ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<title>Site Name &gt;&gt;&gt; Aboot</title>', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_uses_cascade_to_generate_meta($viewType)
    {
        $this
            ->prepareViews($viewType)
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

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<title>Cool Runnings -- Aboot</title>', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_sanitized_title($viewType)
    {
        $this
            ->prepareViews($viewType)
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'title' => "  It's a me, <b>Mario</b>!  ",
                'site_name' => '  Cool "Runnings"  ',
                'site_name_position' => 'before',
                'site_name_separator' => '  >>>  ',
            ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<title>Cool &quot;Runnings&quot; &gt;&gt;&gt; It&#039;s a me, Mario!</title>', false);
        $response->assertSee('<meta property="og:title" content="It&#039;s a me, Mario!" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_sanitized_description($viewType)
    {
        $this
            ->prepareViews($viewType)
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'description' => "  It's a me, <b>Mario</b>!  ",
            ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta name="description" content="It&#039;s a me, Mario!" />', false);
        $response->assertSee('<meta property="og:description" content="It&#039;s a me, Mario!" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_custom_twitter_card_with_short_summary($viewType)
    {
        $this->prepareViews($viewType);

        Config::set('statamic.seo-pro.twitter.card', 'summary');

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta name="twitter:card" content="summary" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_twitter_handle_meta($viewType)
    {
        $this
            ->prepareViews($viewType)
            ->setSeoInSiteDefaults([
                'twitter_handle' => '  itsmario85  ',
            ])
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'twitter_handle' => '@itsluigi85',
            ]);

        $response = $this->get('/');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta name="twitter:site" content="@itsmario85" />', false);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta name="twitter:site" content="@itsluigi85" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_social_image($viewType)
    {
        Config::set('statamic.seo-pro.assets.container', 'assets');

        $this
            ->prepareViews($viewType)
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'image' => 'img/stetson.jpg',
            ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta property="og:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo_pro_og&s=6e3bd8a29425c3b1fcc63d3c0ed02ff8" />', false);
        $response->assertSee('<meta property="og:image:width" content="1146" />', false);
        $response->assertSee('<meta property="og:image:height" content="600" />', false);
        $response->assertSee('<meta name="twitter:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo_pro_twitter&s=3bc5d83bf276b3825695610a6ef88d5b" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     * @environment-setup setCustomGlidePresetDimensions
     */
    public function it_generates_social_image_with_custom_glide_presets($viewType)
    {
        Artisan::call('statamic:glide:clear');

        $this
            ->prepareViews($viewType)
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'image' => 'img/stetson.jpg',
            ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta property="og:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo_pro_og&s=6e3bd8a29425c3b1fcc63d3c0ed02ff8" />', false);
        $response->assertSee('<meta property="og:image:width" content="800" />', false);
        $response->assertSee('<meta property="og:image:height" content="600" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     * @environment-setup setCustomOgGlidePresetOnly
     */
    public function it_generates_social_image_with_og_glide_preset_only($viewType)
    {
        Artisan::call('statamic:glide:clear');

        $this
            ->prepareViews($viewType)
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'image' => 'img/stetson.jpg',
            ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta property="og:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo_pro_og&s=6e3bd8a29425c3b1fcc63d3c0ed02ff8" />', false);
        $response->assertSee('<meta name="twitter:image" content="http://cool-runnings.com/assets/img/stetson.jpg" />', false);
        $response->assertSee('<meta property="og:image:width" content="800" />', false);
        $response->assertSee('<meta property="og:image:height" content="600" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     * @environment-setup setCustomTwitterGlidePresetOnly
     */
    public function it_generates_social_image_with_twitter_glide_preset_only($viewType)
    {
        Artisan::call('statamic:glide:clear');

        $this
            ->prepareViews($viewType)
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'image' => 'img/stetson.jpg',
            ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta property="og:image" content="http://cool-runnings.com/assets/img/stetson.jpg" />', false);
        $response->assertSee('<meta name="twitter:image" content="http://cool-runnings.com/img/asset/YXNzZXRzL2ltZy9zdGV0c29uLmpwZw==?p=seo_pro_twitter&s=3bc5d83bf276b3825695610a6ef88d5b" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_home_url_for_entry_meta($viewType)
    {
        $this->prepareViews($viewType);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link href="http://cool-runnings.com/" rel="home" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_canonical_url_for_entry_meta($viewType)
    {
        $this->prepareViews($viewType);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link href="http://cool-runnings.com/about" rel="canonical" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_canonical_url_for_term_meta($viewType)
    {
        $this->prepareViews($viewType);

        $response = $this->get('/topics/sneakers');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link href="http://cool-runnings.com/topics/sneakers" rel="canonical" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_canonical_url_meta_with_pagination($viewType)
    {
        $this->prepareViews($viewType);

        $response = $this->simulatePageOutOfFive(2);
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link href="http://cool-runnings.com/about?page=2" rel="canonical" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_canonical_url_meta_without_pagination($viewType)
    {
        Config::set('statamic.seo-pro.pagination.enabled_in_canonical_url', false);

        $this->prepareViews($viewType);

        $response = $this->simulatePageOutOfFive(2);
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link href="http://cool-runnings.com/about" rel="canonical" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_rel_next_prev_url_meta($viewType)
    {
        $this->prepareViews($viewType);

        $response = $this->simulatePageOutOfFive(1);
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertDontSee('rel="prev"', false);
        $response->assertSee('<link href="http://cool-runnings.com/about?page=2" rel="next" />', false);

        $response = $this->simulatePageOutOfFive(2);
        $response->assertSee('<link href="http://cool-runnings.com/about" rel="prev" />', false);
        $response->assertSee('<link href="http://cool-runnings.com/about?page=3" rel="next" />', false);

        $response = $this->simulatePageOutOfFive(3);
        $response->assertSee('<link href="http://cool-runnings.com/about?page=2" rel="prev" />', false);
        $response->assertSee('<link href="http://cool-runnings.com/about?page=4" rel="next" />', false);

        $response = $this->simulatePageOutOfFive(5);
        $response->assertSee('<link href="http://cool-runnings.com/about?page=4" rel="prev" />', false);
        $response->assertDontSee('rel="next"', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_rel_next_prev_url_meta_with_first_page_enabled($viewType)
    {
        Config::set('statamic.seo-pro.pagination.enabled_on_first_page', true);

        $this->prepareViews($viewType);

        $response = $this->simulatePageOutOfFive(2);
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link href="http://cool-runnings.com/about?page=1" rel="prev" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_generate_rel_next_prev_url_meta_without_paginator($viewType)
    {
        $this->prepareViews($viewType);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertDontSee('rel="next"', false);
        $response->assertDontSee('rel="prev"', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_generate_any_pagination_when_completely_disabled($viewType)
    {
        Config::set('statamic.seo-pro.pagination', false);

        $this->prepareViews($viewType);

        $response = $this->simulatePageOutOfFive(2);
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertDontSee('page=2', false);
        $response->assertDontSee('rel="next"', false);
        $response->assertDontSee('rel="prev"', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_canonical_url_meta_with_custom_url($viewType)
    {
        $this
            ->prepareViews($viewType)
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'canonical_url' => 'https://hot-walkings.com/pages/aboot',
            ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link href="https://hot-walkings.com/pages/aboot" rel="canonical" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_applies_pagination_to_custom_canonical_url_on_same_domain($viewType)
    {
        $this
            ->prepareViews($viewType)
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'canonical_url' => 'http://cool-runnings.com/pages/aboot',
            ]);

        $response = $this->simulatePageOutOfFive(2);
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link href="http://cool-runnings.com/pages/aboot?page=2" rel="canonical" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_apply_pagination_to_external_custom_canonical_url($viewType)
    {
        $this
            ->prepareViews($viewType)
            ->setSeoOnEntry(Entry::findBySlug('about', 'pages'), [
                'canonical_url' => 'https://hot-walkings.com/pages/aboot',
            ]);

        $response = $this->simulatePageOutOfFive(2);
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link href="https://hot-walkings.com/pages/aboot" rel="canonical" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_apply_pagination_to_first_page($viewType)
    {
        $this->prepareViews($viewType);

        $response = $this->simulatePageOutOfFive(1);

        $response->assertSee('<link href="http://cool-runnings.com/about" rel="canonical" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_can_apply_pagination_to_first_page_when_configured_as_unique_page($viewType)
    {
        Config::set('statamic.seo-pro.pagination.enabled_on_first_page', true);

        $this->prepareViews($viewType);

        $response = $this->simulatePageOutOfFive(1);
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link href="http://cool-runnings.com/about?page=1" rel="canonical" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_robots_meta($viewType)
    {
        $this->prepareViews($viewType);

        $this->setSeoInSiteDefaults([
            'robots' => [
                'noindex',
            ],
        ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta name="robots" content="noindex" />', false);

        $this->setSeoInSiteDefaults([
            'robots' => [
                'noindex',
                'nofollow',
            ],
        ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_custom_humans_url($viewType)
    {
        Config::set('statamic.seo-pro.humans.url', 'aliens.md');

        $this->prepareViews($viewType);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<link type="text/plain" rel="author" href="http://cool-runnings.com/aliens.md" />', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_search_engine_verification_codes($viewType)
    {
        $this
            ->prepareViews($viewType)
            ->setSeoInSiteDefaults([
                'google_verification' => 'google123',
                'bing_verification' => 'bing123',
            ]);

        $response = $this->get('/about');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('<meta name="google-site-verification" content="google123" />', false);
        $response->assertSee('<meta name="msvalidate.01" content="bing123" />', false);
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

        return $this->call('GET', '/about', ['page' => $currentPage]);
    }
}
