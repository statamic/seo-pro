<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;
use Statamic\SeoPro\SectionDefaults\LocalizedSectionDefaults;
use Statamic\SeoPro\SectionDefaults\SectionDefaults;

class SectionDefaultsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Start each test with a clean slate.
        SectionDefaults::clearCache('collections', 'pages');
        SectionDefaults::clearCache('collections', 'articles');
        SectionDefaults::clearCache('taxonomies', 'topics');
    }

    #[Test]
    public function it_returns_localized_defaults_with_no_values_when_no_settings_exist()
    {
        $all = SectionDefaults::get('collections', 'pages');

        $this->assertCount(1, $all);

        $localized = $all->first();
        $this->assertInstanceOf(LocalizedSectionDefaults::class, $localized);
        $this->assertSame('default', $localized->locale());
        $this->assertTrue($localized->isEnabled());
        $this->assertEmpty($localized->all());
    }

    #[Test]
    public function it_returns_disabled_localized_defaults_when_section_is_disabled()
    {
        Addon::get('statamic/seo-pro')->settings()
            ->set('section_defaults', ['collections' => ['pages' => false]])
            ->save();

        $localized = SectionDefaults::in('collections', 'pages', 'default');

        $this->assertFalse($localized->isEnabled());
    }

    #[Test]
    public function it_reports_section_as_enabled_when_no_settings_exist()
    {
        $this->assertTrue(SectionDefaults::isEnabled('collections', 'pages'));
    }

    #[Test]
    public function it_reports_section_as_disabled_after_calling_disable()
    {
        SectionDefaults::disable('collections', 'pages');

        $this->assertFalse(SectionDefaults::isEnabled('collections', 'pages'));
    }

    #[Test]
    public function it_stores_false_in_addon_settings_when_disabling()
    {
        SectionDefaults::disable('collections', 'pages');

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertFalse($raw['section_defaults']['collections']['pages']);
    }

    #[Test]
    public function it_can_save_section_defaults()
    {
        $locale = 'default';
        $localized = SectionDefaults::in('collections', 'pages', $locale);
        $localized->set(['title' => '@seo:title', 'description' => 'My page'])->save();

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertEquals(
            ['title' => '@seo:title', 'description' => 'My page'],
            $raw['section_defaults']['collections']['pages']
        );
    }

    #[Test]
    public function it_stores_values_flat_for_single_site()
    {
        $localized = SectionDefaults::in('collections', 'pages', 'default');
        $localized->set(['title' => '@seo:title'])->save();

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        // No 'sites' nesting in single-site mode.
        $this->assertArrayNotHasKey('sites', $raw['section_defaults']['collections']['pages']);
        $this->assertEquals('@seo:title', $raw['section_defaults']['collections']['pages']['title']);
    }

    #[Test]
    public function it_can_read_saved_defaults_back_via_in()
    {
        $localized = SectionDefaults::in('collections', 'pages', 'default');
        $localized->set(['title' => 'Hello'])->save();

        SectionDefaults::clearCache('collections', 'pages');

        $fresh = SectionDefaults::in('collections', 'pages', 'default');

        $this->assertEquals(['title' => 'Hello'], $fresh->all());
    }

    #[Test]
    public function it_removes_handle_entry_when_saving_empty_values()
    {
        // Save some values first.
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'Hello'])->save();

        SectionDefaults::clearCache('collections', 'pages');

        // Now save empty values — the handle entry should be removed.
        SectionDefaults::in('collections', 'pages', 'default')->set([])->save();

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertArrayNotHasKey('pages', $raw['section_defaults']['collections'] ?? []);
    }

    #[Test]
    public function it_clears_blink_cache_on_save()
    {
        $localized = SectionDefaults::in('collections', 'pages', 'default');
        $localized->set(['title' => 'First'])->save();

        SectionDefaults::clearCache('collections', 'pages');

        // Warm the cache.
        SectionDefaults::get('collections', 'pages');

        // Save new values.
        $localized->set(['title' => 'Second'])->save();

        // Should not return stale cache.
        $fresh = SectionDefaults::in('collections', 'pages', 'default');
        $this->assertEquals('Second', $fresh->all()['title']);
    }

    #[Test]
    public function it_re_enables_a_disabled_section_by_saving_values()
    {
        SectionDefaults::disable('collections', 'pages');
        $this->assertFalse(SectionDefaults::isEnabled('collections', 'pages'));

        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'Hello'])->save();

        $this->assertTrue(SectionDefaults::isEnabled('collections', 'pages'));
    }

    #[Test]
    public function it_can_get_section_defaults_for_a_taxonomy()
    {
        $localized = SectionDefaults::in('taxonomies', 'topics', 'default');
        $localized->set(['title' => 'Topics title'])->save();

        SectionDefaults::clearCache('taxonomies', 'topics');

        $fresh = SectionDefaults::in('taxonomies', 'topics', 'default');
        $this->assertEquals(['title' => 'Topics title'], $fresh->all());
    }

    #[Test]
    public function it_can_disable_a_taxonomy_section()
    {
        SectionDefaults::disable('taxonomies', 'topics');

        $this->assertFalse(SectionDefaults::isEnabled('taxonomies', 'topics'));
    }

    #[Test]
    public function it_returns_null_origin_in_single_site_with_no_origins_configured()
    {
        $localized = SectionDefaults::in('collections', 'pages', 'default');

        $this->assertNull($localized->origin());
        $this->assertFalse($localized->hasOrigin());
    }

    #[Test]
    public function it_returns_values_via_set_method()
    {
        $localized = SectionDefaults::in('collections', 'pages', 'default');

        $localized->set('title', 'My Title');
        $localized->set('description', 'My description');

        $this->assertEquals('My Title', $localized->all()['title']);
        $this->assertEquals('My description', $localized->all()['description']);
    }

    #[Test]
    public function set_with_array_replaces_all_values()
    {
        $localized = SectionDefaults::in('collections', 'pages', 'default');

        $localized->set(['title' => 'First', 'description' => 'Desc']);
        $localized->set(['title' => 'Replaced']);

        $this->assertEquals(['title' => 'Replaced'], $localized->all());
    }

    #[Test]
    public function it_preserves_other_section_defaults_when_saving()
    {
        SectionDefaults::in('collections', 'articles', 'default')->set(['title' => 'Articles'])->save();
        SectionDefaults::clearCache('collections', 'articles');

        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'Pages'])->save();
        SectionDefaults::clearCache('collections', 'pages');

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertEquals('Articles', $raw['section_defaults']['collections']['articles']['title']);
        $this->assertEquals('Pages', $raw['section_defaults']['collections']['pages']['title']);
    }

    #[Test]
    public function it_preserves_disabled_state_when_reading_raw_settings()
    {
        // This test guards against the Antlers resolver converting false to "".
        SectionDefaults::disable('collections', 'pages');

        // Raw settings should have false, not "" or null.
        $raw = Addon::get('statamic/seo-pro')->settings()->raw();
        $this->assertFalse($raw['section_defaults']['collections']['pages']);

        // isEnabled() must return false.
        SectionDefaults::clearCache('collections', 'pages');
        $this->assertFalse(SectionDefaults::isEnabled('collections', 'pages'));
    }

    #[Test]
    public function it_returns_collection_of_localized_defaults_for_each_site()
    {
        $all = SectionDefaults::get('collections', 'pages');

        // Single-site fixture has one site.
        $this->assertCount(1, $all);
        $this->assertInstanceOf(LocalizedSectionDefaults::class, $all->first());
    }

    #[Test]
    public function localized_defaults_is_enabled_returns_false_when_whole_section_is_disabled()
    {
        SectionDefaults::disable('collections', 'pages');
        SectionDefaults::clearCache('collections', 'pages');

        $all = SectionDefaults::get('collections', 'pages');

        foreach ($all as $localized) {
            $this->assertFalse($localized->isEnabled());
        }
    }

    // -------------------------------------------------------------------------
    // inject.seo fallback (legacy storage)
    // -------------------------------------------------------------------------

    #[Test]
    public function it_falls_back_to_inject_seo_when_no_addon_settings_exist()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => '@seo:title', 'description' => 'Legacy desc']])
            ->save();

        $localized = SectionDefaults::in('collections', 'pages', 'default');

        $this->assertTrue($localized->isEnabled());
        $this->assertEquals(['title' => '@seo:title', 'description' => 'Legacy desc'], $localized->all());
    }

    #[Test]
    public function it_reports_section_as_disabled_when_inject_seo_is_false()
    {
        Collection::find('pages')
            ->cascade(['seo' => false])
            ->save();

        $this->assertFalse(SectionDefaults::isEnabled('collections', 'pages'));
    }

    #[Test]
    public function it_prefers_addon_settings_over_inject_seo()
    {
        // Set inject.seo on the YAML.
        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'From YAML']])
            ->save();

        // Set a different value in addon settings.
        Addon::get('statamic/seo-pro')->settings()
            ->set('section_defaults', ['collections' => ['pages' => ['title' => 'From Settings']]])
            ->save();

        $localized = SectionDefaults::in('collections', 'pages', 'default');

        $this->assertEquals('From Settings', $localized->all()['title']);
    }

    #[Test]
    public function it_strips_inject_seo_from_yaml_when_saving_to_addon_settings()
    {
        // Set up legacy inject.seo.
        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'Legacy']])
            ->save();

        // Saving via the new API should migrate and strip inject.seo.
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'Migrated'])->save();

        $collection = Collection::find('pages');
        $this->assertArrayNotHasKey('seo', $collection->cascade()->all());
    }

    #[Test]
    public function it_strips_inject_seo_from_yaml_when_disabling()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'Legacy']])
            ->save();

        SectionDefaults::disable('collections', 'pages');

        $collection = Collection::find('pages');
        $this->assertArrayNotHasKey('seo', $collection->cascade()->all());
    }

    #[Test]
    public function after_lazy_migration_subsequent_reads_use_addon_settings()
    {
        // Set up legacy inject.seo.
        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'Legacy']])
            ->save();

        // Trigger lazy migration by saving.
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'Migrated'])->save();

        // Remove inject.seo from the collection manually to confirm it's gone.
        $collection = Collection::find('pages');
        $this->assertArrayNotHasKey('seo', $collection->cascade()->all());

        // Subsequent read comes from addon settings.
        SectionDefaults::clearCache('collections', 'pages');
        $localized = SectionDefaults::in('collections', 'pages', 'default');
        $this->assertEquals('Migrated', $localized->all()['title']);
    }

    #[Test]
    public function it_falls_back_to_inject_seo_for_taxonomies()
    {
        Taxonomy::find('topics')
            ->cascade(['seo' => ['title' => 'Topics Legacy']])
            ->save();

        $localized = SectionDefaults::in('taxonomies', 'topics', 'default');

        $this->assertTrue($localized->isEnabled());
        $this->assertEquals(['title' => 'Topics Legacy'], $localized->all());
    }

    #[Test]
    public function it_strips_inject_seo_from_taxonomy_yaml_when_saving()
    {
        Taxonomy::find('topics')
            ->cascade(['seo' => ['title' => 'Legacy']])
            ->save();

        SectionDefaults::in('taxonomies', 'topics', 'default')->set(['title' => 'Migrated'])->save();

        $taxonomy = Taxonomy::find('topics');
        $this->assertArrayNotHasKey('seo', $taxonomy->cascade()->all());
    }

    #[Test]
    public function it_does_not_modify_yaml_when_inject_seo_is_absent()
    {
        // No inject.seo in the fixture — saving should not throw or modify YAML unexpectedly.
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'New'])->save();

        // Collection YAML should not have gained an inject.seo key.
        $collection = Collection::find('pages');
        $this->assertArrayNotHasKey('seo', $collection->cascade()->all());
    }
}
