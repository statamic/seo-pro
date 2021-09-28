<?php

namespace Tests\Localized;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
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
    function metaProvider()
    {
        return [
            ['antlersMeta'],
            ['bladeMeta'],
        ];
    }

    private function antlersMeta($uri = null)
    {
        $site = Site::current();
        $data = Data::findByUri(Str::ensureLeft($uri, '/'), $site->handle());
        $context = (new Cascade(request(), $site))->withContent($data)->hydrate()->toArray();

        return (string) Antlers::parse('{{ seo_pro:meta }}', $context);
    }

    private function bladeMeta($uri = null)
    {
        $site = Site::current();
        $data = Data::findByUri(Str::ensureLeft($uri, '/'), $site->handle());
        $context = (new Cascade(request(), $site))->withContent($data)->hydrate()->toArray();

        ob_start() and extract($context, EXTR_SKIP);

        eval('?>' . Blade::compileString('@seo_pro(\'meta\')'));

        return (string) ob_get_clean();
    }

    /** 
     * @test 
     * @dataProvider metaProvider
     */
    public function it_generates_multisite_meta($metaProvider)
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

        $meta = $this->{$metaProvider}();

        $this->assertStringContainsString($expectedOgLocaleMeta, $meta);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $meta);
    }

    /** 
     * @test 
     * @dataProvider metaProvider
     */
    public function it_generates_multisite_meta_for_non_home_page_route($metaProvider)
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

        $meta = $this->{$metaProvider}('about');

        $this->assertStringContainsString($expectedOgLocaleMeta, $meta);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $meta);
    }

    /** 
     * @test 
     * @dataProvider metaProvider
     */
    public function it_doesnt_generate_multisite_meta_when_it_doesnt_exist_for_page($metaProvider)
    {
        $meta = $this->{$metaProvider}('articles');

        $this->assertStringContainsString('og:locale', $meta);
        $this->assertStringNotContainsString('og:locale:alternate', $meta);
        $this->assertStringNotContainsString('hreflang', $meta);
    }

    /** 
     * @test 
     * @dataProvider metaProvider
     */
    public function it_doesnt_generate_multisite_meta_when_alternate_locales_are_disabled($metaProvider)
    {
        Config::set('statamic.seo-pro.alternate_locales', false);

        $meta = $this->{$metaProvider}();

        $this->assertStringContainsString('og:locale', $meta);
        $this->assertStringNotContainsString('og:locale:alternate', $meta);
        $this->assertStringNotContainsString('hreflang', $meta);
    }

    /** 
     * @test 
     * @dataProvider metaProvider
     */
    public function it_doesnt_generate_multisite_meta_for_excluded_sites($metaProvider)
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

        $meta = $this->{$metaProvider}();

        $this->assertStringContainsString($expectedOgLocaleMeta, $meta);
        $this->assertStringContainsString($expectedAlternateHreflangMeta, $meta);
        $this->assertStringNotContainsString('content="fr_FR"', $meta);
        $this->assertStringNotContainsString('hreflang="fr"', $meta);
    }
}
