<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\SeoPro\SectionDefaults\LocalizedSectionDefaults;
use Statamic\SeoPro\SectionDefaults\SectionDefaults;

class SectionDefaultsTest extends LocalizedTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SectionDefaults::clearCache('collections', 'pages');
        SectionDefaults::clearCache('collections', 'articles');
        SectionDefaults::clearCache('taxonomies', 'topics');
    }

    #[Test]
    public function it_returns_one_localized_default_per_site()
    {
        $all = SectionDefaults::get('collections', 'pages');

        // site-localized fixture has default, french, italian, british.
        $this->assertCount(4, $all);
        $all->each(fn ($loc) => $this->assertInstanceOf(LocalizedSectionDefaults::class, $loc));
    }

    #[Test]
    public function it_stores_values_under_sites_key_for_multi_site()
    {
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'En title'])->save();

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertEquals(
            ['title' => 'En title'],
            $raw['section_defaults']['collections']['pages']['sites']['default']
        );
    }

    #[Test]
    public function it_can_save_and_read_back_section_defaults_per_site()
    {
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'English'])->save();
        SectionDefaults::in('collections', 'pages', 'french')->set(['title' => 'French'])->save();

        SectionDefaults::clearCache('collections', 'pages');

        $this->assertEquals(['title' => 'English'], SectionDefaults::in('collections', 'pages', 'default')->all());
        $this->assertEquals(['title' => 'French'], SectionDefaults::in('collections', 'pages', 'french')->all());
    }

    #[Test]
    public function it_disables_section_globally_for_all_sites()
    {
        SectionDefaults::disable('collections', 'pages');

        SectionDefaults::clearCache('collections', 'pages');

        $all = SectionDefaults::get('collections', 'pages');

        foreach ($all as $localized) {
            $this->assertFalse($localized->isEnabled());
        }

        $this->assertFalse(SectionDefaults::isEnabled('collections', 'pages'));
    }

    #[Test]
    public function it_clears_disabled_state_when_saving_site_values()
    {
        SectionDefaults::disable('collections', 'pages');

        // Saving site values should re-enable the section.
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'Re-enabled'])->save();

        SectionDefaults::clearCache('collections', 'pages');

        $this->assertTrue(SectionDefaults::isEnabled('collections', 'pages'));

        // In multi-site mode the values are stored under sites.{locale}.
        $raw = Addon::get('statamic/seo-pro')->settings()->raw();
        $this->assertEquals(
            ['title' => 'Re-enabled'],
            $raw['section_defaults']['collections']['pages']['sites']['default']
        );
    }

    #[Test]
    public function it_removes_site_entry_when_saving_empty_values()
    {
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'English'])->save();
        SectionDefaults::in('collections', 'pages', 'french')->set(['title' => 'French'])->save();

        SectionDefaults::clearCache('collections', 'pages');

        // Clear the french values.
        SectionDefaults::in('collections', 'pages', 'french')->set([])->save();

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();
        $sites = $raw['section_defaults']['collections']['pages']['sites'] ?? [];

        $this->assertArrayNotHasKey('french', $sites);
        $this->assertArrayHasKey('default', $sites);
    }

    #[Test]
    public function it_removes_handle_entry_when_all_sites_have_empty_values()
    {
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'English'])->save();

        SectionDefaults::clearCache('collections', 'pages');

        // Clear the only remaining site.
        SectionDefaults::in('collections', 'pages', 'default')->set([])->save();

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertArrayNotHasKey('pages', $raw['section_defaults']['collections'] ?? []);
    }

    #[Test]
    public function it_resolves_origin_from_site_defaults_sites_config()
    {
        Addon::get('statamic/seo-pro')->settings()->set('site_defaults_sites', [
            'default' => null,
            'french' => 'default',
            'italian' => 'default',
            'british' => 'default',
        ])->save();

        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'English origin'])->save();

        SectionDefaults::clearCache('collections', 'pages');

        $french = SectionDefaults::in('collections', 'pages', 'french');

        $this->assertTrue($french->hasOrigin());

        $origin = $french->origin();
        $this->assertNotNull($origin);
        $this->assertSame('default', $origin->locale());
        $this->assertEquals(['title' => 'English origin'], $origin->all());
    }

    #[Test]
    public function it_has_no_origin_when_site_defaults_sites_config_is_absent()
    {
        $localized = SectionDefaults::in('collections', 'pages', 'french');

        $this->assertFalse($localized->hasOrigin());
        $this->assertNull($localized->origin());
    }

    #[Test]
    public function it_inherits_values_from_origin_via_has_origin_trait()
    {
        Addon::get('statamic/seo-pro')->settings()->set('site_defaults_sites', [
            'default' => null,
            'french' => 'default',
            'italian' => 'default',
            'british' => 'default',
        ])->save();

        SectionDefaults::in('collections', 'pages', 'default')
            ->set(['title' => 'Inherited title', 'description' => 'Inherited desc'])
            ->save();

        SectionDefaults::in('collections', 'pages', 'french')
            ->set(['title' => 'French title'])
            ->save();

        SectionDefaults::clearCache('collections', 'pages');

        $french = SectionDefaults::in('collections', 'pages', 'french');

        // values() merges own + origin through HasOrigin trait.
        $values = $french->values();

        $this->assertEquals('French title', $values->get('title'));
        $this->assertEquals('Inherited desc', $values->get('description'));
    }

    #[Test]
    public function origins_returns_map_from_site_defaults_sites()
    {
        Addon::get('statamic/seo-pro')->settings()->set('site_defaults_sites', [
            'default' => null,
            'french' => 'default',
        ])->save();

        $origins = SectionDefaults::origins();

        $this->assertNull($origins->get('default'));
        $this->assertEquals('default', $origins->get('french'));
    }

    #[Test]
    public function it_preserves_other_sites_when_saving_one_site()
    {
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'English'])->save();
        SectionDefaults::in('collections', 'pages', 'french')->set(['title' => 'French'])->save();

        // Update only default.
        SectionDefaults::clearCache('collections', 'pages');
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'English Updated'])->save();

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();
        $sites = $raw['section_defaults']['collections']['pages']['sites'];

        $this->assertEquals('English Updated', $sites['default']['title']);
        $this->assertEquals('French', $sites['french']['title']);
    }

    #[Test]
    public function it_preserves_disabled_state_across_all_sites()
    {
        // Guards against the Antlers resolver converting false to "" in multi-site.
        SectionDefaults::disable('collections', 'pages');

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();
        $this->assertFalse($raw['section_defaults']['collections']['pages']);

        SectionDefaults::clearCache('collections', 'pages');
        $this->assertFalse(SectionDefaults::isEnabled('collections', 'pages'));
    }

    // -------------------------------------------------------------------------
    // inject.seo fallback (legacy storage) — multi-site
    // -------------------------------------------------------------------------

    #[Test]
    public function it_falls_back_to_inject_seo_and_assigns_to_default_site()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'Legacy title']])
            ->save();

        $defaultHandle = Site::default()->handle();

        $localized = SectionDefaults::in('collections', 'pages', $defaultHandle);

        $this->assertTrue($localized->isEnabled());
        $this->assertEquals(['title' => 'Legacy title'], $localized->all());
    }

    #[Test]
    public function inject_seo_fallback_returns_empty_for_non_default_sites()
    {
        // inject.seo has no per-site support — only the default site gets values.
        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'Legacy title']])
            ->save();

        $french = SectionDefaults::in('collections', 'pages', 'french');

        // French has no own values from inject.seo fallback.
        $this->assertEmpty($french->all());
    }

    #[Test]
    public function inject_seo_fallback_values_are_inherited_by_child_sites_via_origin()
    {
        Addon::get('statamic/seo-pro')->settings()->set('site_defaults_sites', [
            'default' => null,
            'french' => 'default',
            'italian' => 'default',
            'british' => 'default',
        ])->save();

        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'Legacy title', 'description' => 'Desc']])
            ->save();

        $french = SectionDefaults::in('collections', 'pages', 'french');

        // French has origin=default, so values() includes inherited values.
        $inherited = $french->values();
        $this->assertEquals('Legacy title', $inherited->get('title'));
        $this->assertEquals('Desc', $inherited->get('description'));
    }

    #[Test]
    public function it_reports_disabled_from_inject_seo_in_multisite()
    {
        Collection::find('pages')
            ->cascade(['seo' => false])
            ->save();

        $this->assertFalse(SectionDefaults::isEnabled('collections', 'pages'));

        SectionDefaults::clearCache('collections', 'pages');

        $all = SectionDefaults::get('collections', 'pages');
        foreach ($all as $localized) {
            $this->assertFalse($localized->isEnabled());
        }
    }

    #[Test]
    public function saving_strips_inject_seo_from_yaml_in_multisite()
    {
        Collection::find('pages')
            ->cascade(['seo' => ['title' => 'Legacy']])
            ->save();

        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'Migrated'])->save();

        $collection = Collection::find('pages');
        $this->assertArrayNotHasKey('seo', $collection->cascade()->all());

        // Value is now under addon settings sites key.
        $raw = Addon::get('statamic/seo-pro')->settings()->raw();
        $this->assertEquals(
            ['title' => 'Migrated'],
            $raw['section_defaults']['collections']['pages']['sites'][Site::default()->handle()]
        );
    }
}
