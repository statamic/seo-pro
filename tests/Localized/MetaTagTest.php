<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Tests\ViewScenarios;

class MetaTagTest extends LocalizedTestCase
{
    use ViewScenarios;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('view.paths', [$this->viewsPath()]);
    }

    protected function tearDown(): void
    {
        $this->cleanUpViews();

        parent::tearDown();
    }

    #[Test]
    #[DataProvider('viewScenarioProvider')]
    public function it_generates_multisite_meta($viewType)
    {
        $this->prepareViews($viewType);

        $expectedOgLocaleMeta = <<<'EOT'
<meta property="og:locale" content="en_US" />
<meta property="og:locale:alternate" content="fr_FR" />
<meta property="og:locale:alternate" content="it_IT" />
<meta property="og:locale:alternate" content="en_GB" />
EOT;

        $expectedAlternateHreflangMeta = <<<'EOT'
<link rel="alternate" href="http://cool-runnings.com" hreflang="en-us" />
<link rel="alternate" href="http://cool-runnings.com/fr" hreflang="fr" />
<link rel="alternate" href="http://corse-fantastiche.it" hreflang="it" />
<link rel="alternate" href="http://cool-runnings.com/en-gb" hreflang="en-gb" />
EOT;

        $content = $this->get('/')->content();

        $this->assertStringContainsStringIgnoringLineEndings("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedOgLocaleMeta, $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedAlternateHreflangMeta, $content);
    }

    #[Test]
    #[DataProvider('viewScenarioProvider')]
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
<link rel="alternate" href="http://corse-fantastiche.it/about" hreflang="it" />
EOT;

        $content = $this->get('/about')->content();

        $this->assertStringContainsStringIgnoringLineEndings("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedOgLocaleMeta, $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedAlternateHreflangMeta, $content);
    }

    #[Test]
    #[DataProvider('viewScenarioProvider')]
    public function it_doesnt_generate_multisite_meta_when_it_doesnt_exist_for_page($viewType)
    {
        $this->prepareViews($viewType);

        $response = $this->get('/articles');
        $response->assertSee("<h1>{$viewType}</h1>", false);
        $response->assertSee('og:locale', false);
        $response->assertDontSee('og:locale:alternate', false);
        $response->assertDontSee('hreflang', false);
    }

    #[Test]
    #[DataProvider('viewScenarioProvider')]
    public function it_generates_multisite_meta_for_canonical_url_and_alternate_locales($viewType)
    {
        $this->prepareViews($viewType);

        $expectedOgLocaleMeta = <<<'EOT'
<meta property="og:locale" content="fr_FR" />
<meta property="og:locale:alternate" content="en_US" />
<meta property="og:locale:alternate" content="it_IT" />
EOT;

        $expectedAlternateHreflangMeta = <<<'EOT'
<link href="http://cool-runnings.com/fr/about" rel="canonical" />
<link rel="alternate" href="http://cool-runnings.com/fr/about" hreflang="fr" />
<link rel="alternate" href="http://cool-runnings.com/about" hreflang="en" />
<link rel="alternate" href="http://corse-fantastiche.it/about" hreflang="it" />
EOT;

        // Though hitting a route will automatically set the current site,
        // we want to test that the alternate locales are generated off
        // the entry's model, not from the current site in the cp.
        Site::setCurrent('default');

        $content = $this->get('/fr/about')->content();

        $this->assertStringContainsStringIgnoringLineEndings("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedOgLocaleMeta, $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedAlternateHreflangMeta, $content);
    }

    #[Test]
    #[DataProvider('viewScenarioProvider')]
    public function it_handles_duplicate_alternate_hreflangs($viewType)
    {
        $this->prepareViews($viewType);

        $expectedAlternateHreflangMeta = <<<'EOT'
<link href="http://cool-runnings.com/fr" rel="canonical" />
<link rel="alternate" href="http://cool-runnings.com/fr" hreflang="fr" />
<link rel="alternate" href="http://cool-runnings.com" hreflang="en-us" />
<link rel="alternate" href="http://corse-fantastiche.it" hreflang="it" />
<link rel="alternate" href="http://cool-runnings.com/en-gb" hreflang="en-gb" />
EOT;

        // Though hitting a route will automatically set the current site,
        // we want to test that the alternate locales are generated off
        // the entry's model, not from the current site in the cp.
        Site::setCurrent('default');

        $content = $this->get('/fr')->content();

        $this->assertStringContainsStringIgnoringLineEndings("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedAlternateHreflangMeta, $content);
    }

    #[Test]
    #[DataProvider('viewScenarioProvider')]
    public function it_handles_duplicate_current_hreflang($viewType)
    {
        $this->prepareViews($viewType);

        $expectedAlternateHreflangMeta = <<<'EOT'
<link href="http://cool-runnings.com/en-gb" rel="canonical" />
<link rel="alternate" href="http://cool-runnings.com/en-gb" hreflang="en-gb" />
<link rel="alternate" href="http://cool-runnings.com" hreflang="en-us" />
<link rel="alternate" href="http://cool-runnings.com/fr" hreflang="fr" />
<link rel="alternate" href="http://corse-fantastiche.it" hreflang="it" />
EOT;

        // Though hitting a route will automatically set the current site,
        // we want to test that the alternate locales are generated off
        // the entry's model, not from the current site in the cp.
        Site::setCurrent('default');

        $content = $this->get('/en-gb')->content();

        $this->assertStringContainsStringIgnoringLineEndings("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedAlternateHreflangMeta, $content);
    }

    #[Test]
    #[DataProvider('viewScenarioProvider')]
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

    #[Test]
    #[DataProvider('viewScenarioProvider')]
    public function it_doesnt_generate_multisite_meta_for_excluded_sites($viewType)
    {
        Config::set('statamic.seo-pro.alternate_locales.excluded_sites', ['french']);

        $this->prepareViews($viewType);

        $expectedOgLocaleMeta = <<<'EOT'
<meta property="og:locale" content="en_US" />
<meta property="og:locale:alternate" content="it_IT" />
EOT;

        $expectedAlternateHreflangMeta = <<<'EOT'
<link rel="alternate" href="http://cool-runnings.com/about" hreflang="en" />
<link rel="alternate" href="http://corse-fantastiche.it/about" hreflang="it" />
EOT;

        $content = $this->get('/about')->content();

        $this->assertStringContainsStringIgnoringLineEndings("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedOgLocaleMeta, $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedAlternateHreflangMeta, $content);
        $this->assertStringNotContainsStringIgnoringLineEndings('content="fr_FR"', $content);
        $this->assertStringNotContainsStringIgnoringLineEndings('hreflang="fr"', $content);
    }

    #[Test]
    #[DataProvider('viewScenarioProvider')]
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
<link rel="alternate" href="http://corse-fantastiche.it/about" hreflang="it" />
EOT;

        $content = $this->get('/about')->content();

        $this->assertStringContainsStringIgnoringLineEndings("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedOgLocaleMeta, $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedAlternateHreflangMeta, $content);
    }

    #[Test]
    #[DataProvider('viewScenarioProvider')]
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
<link rel="alternate" href="http://corse-fantastiche.it/about" hreflang="it" />
EOT;

        $content = $this->get('/about')->content();

        $this->assertStringContainsStringIgnoringLineEndings("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedOgLocaleMeta, $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedAlternateHreflangMeta, $content);
    }

    #[Test]
    #[DataProvider('viewScenarioProvider')]
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
<link rel="alternate" href="http://corse-fantastiche.it/about" hreflang="it" />
EOT;

        $content = $this->get('/about')->content();

        $this->assertStringContainsStringIgnoringLineEndings("<h1>{$viewType}</h1>", $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedOgLocaleMeta, $content);
        $this->assertStringContainsStringIgnoringLineEndings($expectedAlternateHreflangMeta, $content);
    }
}
