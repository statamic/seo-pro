<?php

namespace Tests\UpdateScripts;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;
use Statamic\SeoPro\UpdateScripts\MigrateSectionDefaultsToAddonSettings;
use Statamic\Testing\Concerns\RunsUpdateScripts;
use Tests\TestCase;

class MigrateSectionDefaultsToAddonSettingsTest extends TestCase
{
    use RunsUpdateScripts;

    // -------------------------------------------------------------------------
    // Collections
    // -------------------------------------------------------------------------

    #[Test]
    public function it_migrates_collection_with_seo_defaults()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => '@seo:title', 'description' => 'My desc']])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertEquals(
            ['title' => '@seo:title', 'description' => 'My desc'],
            $raw['section_defaults']['collections']['pages']
        );
    }

    #[Test]
    public function it_migrates_disabled_collection()
    {
        Collection::find('pages')
            ->cascade(['seo' => false])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertFalse($raw['section_defaults']['collections']['pages']);
    }

    #[Test]
    public function it_skips_collections_without_inject_seo()
    {
        // The pages collection has no inject.seo in the fixture — run script and verify nothing added.
        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertEmpty($raw['section_defaults']['collections']['pages'] ?? []);
    }

    #[Test]
    public function it_removes_inject_seo_from_collection_yaml_after_migration()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => '@seo:title']])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        // Reload collection from disk and check cascade no longer has 'seo'.
        $collection = Collection::find('pages');
        $this->assertNull($collection->cascade('seo'));
        $this->assertArrayNotHasKey('seo', $collection->cascade()->all());
    }

    #[Test]
    public function it_removes_inject_seo_from_disabled_collection_yaml_after_migration()
    {
        Collection::find('pages')
            ->cascade(['seo' => false])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $collection = Collection::find('pages');
        $this->assertArrayNotHasKey('seo', $collection->cascade()->all());
    }

    #[Test]
    public function it_migrates_multiple_collections()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'Pages title']])
            ->save();

        Collection::find('articles')
            ->cascade(['seo' => false])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertEquals(
            ['title' => 'Pages title'],
            $raw['section_defaults']['collections']['pages']
        );

        $this->assertFalse($raw['section_defaults']['collections']['articles']);
    }

    #[Test]
    public function it_skips_empty_inject_seo_array()
    {
        // An empty inject.seo array should not be migrated.
        Collection::find('pages')
            ->cascade(['seo' => []])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertEmpty($raw['section_defaults']['collections']['pages'] ?? []);
    }

    // -------------------------------------------------------------------------
    // Taxonomies
    // -------------------------------------------------------------------------

    #[Test]
    public function it_migrates_taxonomy_with_seo_defaults()
    {
        Taxonomy::find('topics')
            ->cascade(['seo' => ['title' => 'Topics title']])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertEquals(
            ['title' => 'Topics title'],
            $raw['section_defaults']['taxonomies']['topics']
        );
    }

    #[Test]
    public function it_migrates_disabled_taxonomy()
    {
        Taxonomy::find('topics')
            ->cascade(['seo' => false])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertFalse($raw['section_defaults']['taxonomies']['topics']);
    }

    #[Test]
    public function it_removes_inject_seo_from_taxonomy_yaml_after_migration()
    {
        Taxonomy::find('topics')
            ->cascade(['seo' => ['title' => 'Topics title']])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $taxonomy = Taxonomy::find('topics');
        $this->assertArrayNotHasKey('seo', $taxonomy->cascade()->all());
    }

    #[Test]
    public function it_migrates_both_collections_and_taxonomies()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'Pages title']])
            ->save();

        Taxonomy::find('topics')
            ->cascade(['seo' => false])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertEquals(
            ['title' => 'Pages title'],
            $raw['section_defaults']['collections']['pages']
        );

        $this->assertFalse($raw['section_defaults']['taxonomies']['topics']);
    }

    #[Test]
    public function it_merges_with_existing_addon_settings()
    {
        // Pre-existing addon settings (e.g. site defaults already migrated).
        Addon::get('statamic/seo-pro')->settings()
            ->set('site_defaults', ['title' => 'My Site'])
            ->save();

        Collection::find('pages')
            ->cascade(['seo' => ['title' => '@seo:title']])
            ->save();

        $this->runUpdateScript(MigrateSectionDefaultsToAddonSettings::class);

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        // Site defaults must be preserved.
        $this->assertEquals('My Site', $raw['site_defaults']['title']);

        // Section defaults must be added.
        $this->assertEquals(
            ['title' => '@seo:title'],
            $raw['section_defaults']['collections']['pages']
        );
    }
}
