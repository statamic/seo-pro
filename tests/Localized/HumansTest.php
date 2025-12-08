<?php

namespace Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;
use Tests\Localized\LocalizedTestCase;

class HumansTest extends LocalizedTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->files->exists($folder = resource_path('views/vendor/seo-pro'))) {
            $this->files->deleteDirectory($folder);
        }

        $this->files->makeDirectory($folder, 0755, true);
    }

    #[Test]
    public function it_outputs_localized_humans_txt()
    {
        SiteDefaults::in('default')
            ->set('site_name', 'Cool Runnings')
            ->set('description', 'I see a bad-ass mother.')
            ->save();

        $content = $this
            ->get('http://cool-runnings.com/humans.txt')
            ->assertOk()
            ->assertContentType('text/plain; charset=utf-8')
            ->getContent();

        $expected = <<<'EOT'
/* TEAM */

Creator: Cool Runnings
URL: http://cool-runnings.com
Description: I see a bad-ass mother.

/* THANKS */

Statamic: https://statamic.com

/* SITE */

Standards: HTML5, CSS3
Components: Statamic, Laravel, PHP, JavaScript

EOT;

        $this->assertEquals($expected, $content);

        SiteDefaults::in('italian')
            ->set('site_name', 'Freddo Correnti')
            ->set('description', 'Vedo una madre tosta.')
            ->save();

        $content = $this
            ->get('http://corse-fantastiche.it/humans.txt')
            ->assertOk()
            ->assertContentType('text/plain; charset=utf-8')
            ->getContent();

        $expected = <<<'EOT'
/* TEAM */

Creator: Freddo Correnti
URL: http://corse-fantastiche.it
Description: Vedo una madre tosta.

/* THANKS */

Statamic: https://statamic.com

/* SITE */

Standards: HTML5, CSS3
Components: Statamic, Laravel, PHP, JavaScript

EOT;

        $this->assertEquals($expected, $content);
    }
}
