<?php

namespace Tests\Localized;

use Statamic\Facades\Config;
use Statamic\Facades\Entry;
use Tests\TestCase;
use Tests\ViewScenarios;

class MetaTagTest extends TestCase
{
    use ViewScenarios;

    protected $siteFixturePath = __DIR__.'/../Fixtures/site-localized';

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('view.paths', [$this->viewsPath()]);
        $app['config']->set('statamic.editions.pro', true);
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
    public function it_generates_multisite_meta($viewType)
    {
        $this->prepareViews($viewType);

        $expectedOgLocaleMeta = <<<'EOT'
<meta property="og:locale" content="en_US" />
<meta property="og:locale:alternate" content="fr_FR" />
<meta property="og:locale:alternate" content="it_IT" />
EOT;

        $expectedAlternateHreflangMeta = <<<'EOT'
<link rel="alternate" href="http://cool-runnings.com" hreflang="en" />
<link rel="alternate" href="http://cool-runnings.com/fr" hreflang="fr" />
<link rel="alternate" href="http://cool-runnings.com/it" hreflang="it" />
EOT;

        $content = $this->get('/')->content();

        $this->assertStringContainsString("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsString($expectedOgLocaleMeta, $content);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $content);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_generates_multisite_meta_for_non_home_page_route($viewType)
    {
        $this->prepareViews($viewType);

        $expectedOgLocaleMeta = <<<'EOT'
<meta property="og:locale" content="en_US" />
<meta property="og:locale:alternate" content="fr_FR" />
<meta property="og:locale:alternate" content="it_IT" />
EOT;

        $expectedAlternateHreflangMeta = <<<'EOT'
<link rel="alternate" href="http://cool-runnings.com/about" hreflang="en" />
<link rel="alternate" href="http://cool-runnings.com/fr/about" hreflang="fr" />
<link rel="alternate" href="http://cool-runnings.com/it/about" hreflang="it" />
EOT;

        $content = $this->get('/about')->content();

        $this->assertStringContainsString("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsString($expectedOgLocaleMeta, $content);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $content);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_generate_multisite_meta_when_it_doesnt_exist_for_page($viewType)
    {
        $this->prepareViews($viewType);

        $response = $this->get('/articles');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('og:locale', false);
        $response->assertDontSee('og:locale:alternate', false);
        $response->assertDontSee('hreflang', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_generate_multisite_meta_when_alternate_locales_are_disabled($viewType)
    {
        Config::set('statamic.seo-pro.alternate_locales', false);

        $this->prepareViews($viewType);

        $response = $this->get('/');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('og:locale', false);
        $response->assertDontSee('og:locale:alternate', false);
        $response->assertDontSee('hreflang', false);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_generate_multisite_meta_for_excluded_sites($viewType)
    {
        Config::set('statamic.seo-pro.alternate_locales.excluded_sites', ['french']);

        $this->prepareViews($viewType);

        $expectedOgLocaleMeta = <<<'EOT'
<meta property="og:locale" content="en_US" />
<meta property="og:locale:alternate" content="it_IT" />
EOT;

        $expectedAlternateHreflangMeta = <<<'EOT'
<link rel="alternate" href="http://cool-runnings.com" hreflang="en" />
<link rel="alternate" href="http://cool-runnings.com/it" hreflang="it" />
EOT;

        $content = $this->get('/')->content();

        $this->assertStringContainsString("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsString($expectedOgLocaleMeta, $content);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $content);
        $this->assertStringNotContainsString('content="fr_FR"', $content);
        $this->assertStringNotContainsString('hreflang="fr"', $content);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_generate_multisite_meta_for_unpublished_content($viewType)
    {
        $this->prepareViews($viewType);

        Entry::find('62136fa2-9e5c-4c38-a894-a2753f02f5ff')->in('french')->unpublish()->save();

        $expectedOgLocaleMeta = <<<'EOT'
<meta property="og:locale" content="en_US" />
<meta property="og:locale:alternate" content="it_IT" />
EOT;

        $expectedAlternateHreflangMeta = <<<'EOT'
<link rel="alternate" href="http://cool-runnings.com/about" hreflang="en" />
<link rel="alternate" href="http://cool-runnings.com/it/about" hreflang="it" />
EOT;

        $content = $this->get('/about')->content();

        $this->assertStringContainsString("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsString($expectedOgLocaleMeta, $content);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $content);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_generate_multisite_meta_for_scheduled_content($viewType)
    {
        $this->prepareViews($viewType);

        $entry = Entry::find('62136fa2-9e5c-4c38-a894-a2753f02f5ff');

        $collection = $entry->collection()->dated(true)->futureDateBehavior('private')->save();

        $entry->in('default')->date(now()->subDays(5))->save();
        $entry->in('french')->date(now()->addDays(3))->save(); // This entry is scheduled, should not show
        $entry->in('italian')->date(now()->subDays(5))->save();

        $expectedOgLocaleMeta = <<<'EOT'
<meta property="og:locale" content="en_US" />
<meta property="og:locale:alternate" content="it_IT" />
EOT;

        $expectedAlternateHreflangMeta = <<<'EOT'
<link rel="alternate" href="http://cool-runnings.com/about" hreflang="en" />
<link rel="alternate" href="http://cool-runnings.com/it/about" hreflang="it" />
EOT;

        $content = $this->get('/about')->content();

        $this->assertStringContainsString("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsString($expectedOgLocaleMeta, $content);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $content);
    }

    /**
     * @test
     * @dataProvider viewScenarioProvider
     */
    public function it_doesnt_generate_multisite_meta_for_expired_content($viewType)
    {
        $this->prepareViews($viewType);

        $entry = Entry::find('62136fa2-9e5c-4c38-a894-a2753f02f5ff');

        $collection = $entry->collection()->dated(true)->pastDateBehavior('private')->save();

        $entry->in('default')->date(now()->addDays(5))->save();
        $entry->in('french')->date(now()->subDays(3))->save(); // This entry is expired, should not show
        $entry->in('italian')->date(now()->addDays(5))->save();

        $expectedOgLocaleMeta = <<<'EOT'
<meta property="og:locale" content="en_US" />
<meta property="og:locale:alternate" content="it_IT" />
EOT;

        $expectedAlternateHreflangMeta = <<<'EOT'
<link rel="alternate" href="http://cool-runnings.com/about" hreflang="en" />
<link rel="alternate" href="http://cool-runnings.com/it/about" hreflang="it" />
EOT;

        $content = $this->get('/about')->content();

        $this->assertStringContainsString("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsString($expectedOgLocaleMeta, $content);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $content);
    }
}
