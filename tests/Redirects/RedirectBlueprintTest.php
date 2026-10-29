<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Site;
use Statamic\SeoPro\Facades;
use Tests\TestCase;

class RedirectBlueprintTest extends TestCase
{
    #[Test]
    public function destination_does_not_select_across_sites_on_a_single_site()
    {
        $this->assertFalse(Facades\Redirect::blueprint()->field('destination')->get('select_across_sites'));
    }

    #[Test]
    public function destination_selects_across_sites_when_multisite()
    {
        config(['statamic.system.multisite' => true, 'statamic.editions.pro' => true]);

        Site::setSites([
            'en' => ['name' => 'English', 'url' => 'http://example.com/', 'locale' => 'en_US'],
            'fr' => ['name' => 'French', 'url' => 'http://example.fr/', 'locale' => 'fr_FR'],
        ]);

        $this->assertTrue(Facades\Redirect::blueprint()->field('destination')->get('select_across_sites'));
    }
}
