<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\SeoPro\UpdateScripts\MigrateToAddonSettings;

class MigrateToAddonSettingsTest extends TestCase
{
    #[Test]
    public function it_migrates_site_defaults_to_addon_settings()
    {
        $this->app['files']->put(base_path('content/seo.yaml'), <<<'EOT'
title: 'My Site'
description: 'Just another Statamic site'
EOT
        );

        $this->runUpdateScript();

        $settings = Addon::get('statamic/seo-pro')->settings();

        $this->assertEquals([
            'title' => 'My Site',
            'description' => 'Just another Statamic site',
        ], $settings->raw());

        $this->assertFileDoesNotExist(base_path('content/seo.yaml'));
    }

    private function runUpdateScript()
    {
        $script = new MigrateToAddonSettings('statamic/seo-pro');

        $script->update();

        return $script;
    }
}