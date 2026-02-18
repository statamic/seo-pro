<?php

namespace Tests\UpdateScripts;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\SeoPro\UpdateScripts\MigrateToAddonSettings;
use Statamic\Testing\Concerns\RunsUpdateScripts;
use Tests\TestCase;

class MigrateToAddonSettingsTest extends TestCase
{
    use RunsUpdateScripts;
    
    #[Test]
    public function it_migrates_site_defaults_to_addon_settings()
    {
        $this->app['files']->put(base_path('content/seo.yaml'), <<<'EOT'
title: 'My Site'
description: 'Just another Statamic site'
EOT
        );

        $this->runUpdateScript(MigrateToAddonSettings::class);

        $settings = Addon::get('statamic/seo-pro')->settings();

        $this->assertEquals([
            'site_defaults' => [
                'title' => 'My Site',
                'description' => 'Just another Statamic site',
            ],
        ], $settings->raw());

        $this->assertFileDoesNotExist(base_path('content/seo.yaml'));
    }
}
