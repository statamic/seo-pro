<?php

namespace Tests\Localized;

use Illuminate\Pagination\LengthAwarePaginator;
use Statamic\Facades\Antlers;
use Statamic\Facades\Blink;
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
    public function it_generates_multisite_meta()
    {
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

        $meta = $this->meta();

        $this->assertStringContainsString($expectedOgLocaleMeta, $meta);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $meta);
    }

    /** @test */
    public function it_generates_multisite_meta_for_non_home_page_route()
    {
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

        $meta = $this->meta('about');

        $this->assertStringContainsString($expectedOgLocaleMeta, $meta);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $meta);
    }

    /** @test */
    public function it_doesnt_generate_multisite_meta_when_it_doesnt_exist_for_page()
    {
        $meta = $this->meta('articles');

        $this->assertStringContainsString('og:locale', $meta);
        $this->assertStringNotContainsString('og:locale:alternate', $meta);
        $this->assertStringNotContainsString('hreflang', $meta);
    }

    /** @test */
    public function it_doesnt_generate_multisite_meta_when_alternate_locales_are_disabled()
    {
        Config::set('statamic.seo-pro.alternate_locales', false);

        $meta = $this->meta();

        $this->assertStringContainsString('og:locale', $meta);
        $this->assertStringNotContainsString('og:locale:alternate', $meta);
        $this->assertStringNotContainsString('hreflang', $meta);
    }

    /** @test */
    public function it_doesnt_generate_multisite_meta_for_excluded_sites()
    {
        Config::set('statamic.seo-pro.alternate_locales.excluded_sites', ['french']);

        $expectedOgLocaleMeta = <<<'EOT'
<meta property="og:locale" content="en_US" />
<meta property="og:locale:alternate" content="it_IT" />
EOT;

        $expectedAlternateHreflangMeta = <<<'EOT'
<link rel="alternate" href="http://cool-runnings.com" hreflang="en" />
<link rel="alternate" href="http://cool-runnings.com/it" hreflang="it" />
EOT;

        $meta = $this->meta();

        $this->assertStringContainsString($expectedOgLocaleMeta, $meta);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $meta);
        $this->assertStringNotContainsString('content="fr_FR"', $meta);
        $this->assertStringNotContainsString('hreflang="fr"', $meta);
    }
}
