<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\SeoPro\UpdateScripts\MigrateSectionDefaultsToAddonSettings;
use Statamic\Testing\Concerns\RunsUpdateScripts;

class MigrateSectionDefaultsToAddonSettingsTest extends LocalizedTestCase
{
    use RunsUpdateScripts;

    #[Test]
    public function it_migrates_collection_seo_defaults_under_sites_key_in_multisite()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => '@seo:title']])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $defaultHandle = Site::default()->handle();

        $this->assertEquals(
            ['title' => '@seo:title'],
            $raw['section_defaults']['collections']['pages']['sites'][$defaultHandle]
        );
    }

    #[Test]
    public function it_migrates_disabled_collection_as_false_in_multisite()
    {
        Collection::find('pages')
            ->cascade(['seo' => false])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        // Disabled collections are always stored as false at the top level, not under sites.
        $this->assertFalse($raw['section_defaults']['collections']['pages']);
    }

    #[Test]
    public function it_removes_inject_seo_from_collection_yaml_in_multisite()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => '@seo:title']])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $collection = Collection::find('pages');
        $this->assertArrayNotHasKey('seo', $collection->cascade()->all());
    }

    #[Test]
    public function it_assigns_seo_data_to_default_site_only_in_multisite()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'English defaults']])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $sites = $raw['section_defaults']['collections']['pages']['sites'];

        // Only the default site should have values after migration.
        $this->assertCount(1, $sites);
        $this->assertArrayHasKey(Site::default()->handle(), $sites);
        $this->assertArrayNotHasKey('french', $sites);
        $this->assertArrayNotHasKey('italian', $sites);
        $this->assertArrayNotHasKey('british', $sites);
    }
}
