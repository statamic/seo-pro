<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\Facades\User;
use Statamic\SeoPro\SectionDefaults\SectionDefaults;

class SectionDefaultsControllerTest extends LocalizedTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SectionDefaults::clearCache('collections', 'pages');
        SectionDefaults::clearCache('collections', 'articles');
        SectionDefaults::clearCache('taxonomies', 'topics');

        // Set up origins so we can test inheritance.
        Addon::get('statamic/seo-pro')->settings()->set('site_defaults_sites', [
            'default' => null,
            'french' => 'default',
            'italian' => 'default',
            'british' => 'default',
        ])->save();
    }

    // -------------------------------------------------------------------------
    // Edit (GET) — multi-site
    // -------------------------------------------------------------------------

    #[Test]
    public function edit_defaults_to_selected_site()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit');

        $response->assertOk();
        $this->assertEquals('default', $response->json('initialSite'));
    }

    #[Test]
    public function can_edit_section_defaults_for_a_specific_site()
    {
        SectionDefaults::in('collections', 'pages', 'french')->set(['title' => 'French title'])->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit?site=french');

        $response->assertOk();
        $this->assertEquals('french', $response->json('initialSite'));
        // seo_pro_source preProcess transforms 'French title' to {source: 'custom', value: 'French title'}.
        $this->assertEquals(['source' => 'custom', 'value' => 'French title'], $response->json('initialValues.title'));
    }

    #[Test]
    public function edit_returns_all_sites_in_localizations()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit');

        $localizations = $response->json('initialLocalizations');

        $this->assertCount(4, $localizations);
        $handles = collect($localizations)->pluck('handle')->all();
        $this->assertContains('default', $handles);
        $this->assertContains('french', $handles);
        $this->assertContains('italian', $handles);
        $this->assertContains('british', $handles);
    }

    #[Test]
    public function edit_marks_requested_site_as_active()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit?site=french');

        $localizations = collect($response->json('initialLocalizations'));

        $active = $localizations->firstWhere('active', true);
        $this->assertEquals('french', $active['handle']);
    }

    #[Test]
    public function edit_indicates_has_origin_for_child_site()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit?site=french');

        $response->assertOk();
        $this->assertTrue($response->json('initialHasOrigin'));
        $this->assertNotNull($response->json('initialOriginValues'));
    }

    #[Test]
    public function edit_indicates_no_origin_for_root_site()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit?site=default');

        $response->assertOk();
        $this->assertFalse($response->json('initialHasOrigin'));
        $this->assertNull($response->json('initialOriginValues'));
    }

    #[Test]
    public function edit_origin_values_come_from_parent_site()
    {
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'Origin title'])->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit?site=french');

        $response->assertOk();
        // seo_pro_source preProcess transforms 'Origin title' to {source: 'custom', value: 'Origin title'}.
        $this->assertEquals(['source' => 'custom', 'value' => 'Origin title'], $response->json('initialOriginValues.title'));
    }

    #[Test]
    public function edit_shows_section_disabled_for_all_sites_when_disabled()
    {
        SectionDefaults::disable('collections', 'pages');

        foreach (['default', 'french', 'italian', 'british'] as $site) {
            $response = $this
                ->actingAs(User::make()->makeSuper()->save())
                ->getJson("/cp/seo-pro/section-defaults/collections/pages/edit?site={$site}");

            $response->assertOk();
            $this->assertFalse($response->json('initialEnabled'), "Expected disabled for site {$site}");
        }
    }

    // -------------------------------------------------------------------------
    // Update (PATCH) — multi-site
    // -------------------------------------------------------------------------

    #[Test]
    public function can_update_section_defaults_for_a_specific_site()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/section-defaults/collections/pages?site=french', [
                'title' => ['source' => 'custom', 'value' => 'French SEO title'],
                'enabled' => true,
            ])
            ->assertOk();

        SectionDefaults::clearCache('collections', 'pages');

        $french = SectionDefaults::in('collections', 'pages', 'french');
        $this->assertEquals('French SEO title', $french->all()['title']);
    }

    #[Test]
    public function updating_one_site_does_not_overwrite_another()
    {
        SectionDefaults::in('collections', 'pages', 'default')->set(['title' => 'English title'])->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/section-defaults/collections/pages?site=french', [
                'title' => ['source' => 'custom', 'value' => 'French title'],
                'enabled' => true,
            ])
            ->assertOk();

        SectionDefaults::clearCache('collections', 'pages');

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();
        $this->assertEquals('English title', $raw['section_defaults']['collections']['pages']['sites']['default']['title']);
        $this->assertEquals('French title', $raw['section_defaults']['collections']['pages']['sites']['french']['title']);
    }

    #[Test]
    public function can_disable_collection_via_update_in_multisite()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/section-defaults/collections/pages?site=french', [
                'enabled' => false,
            ])
            ->assertOk();

        SectionDefaults::clearCache('collections', 'pages');

        // Disabled state is global — all sites should report disabled.
        $this->assertFalse(SectionDefaults::isEnabled('collections', 'pages'));

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();
        $this->assertFalse($raw['section_defaults']['collections']['pages']);
    }

    #[Test]
    public function update_stores_values_under_sites_key_for_multi_site()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/section-defaults/collections/pages?site=default', [
                'title' => ['source' => 'field', 'value' => 'title'],
                'enabled' => true,
            ])
            ->assertOk();

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();

        $this->assertArrayHasKey('sites', $raw['section_defaults']['collections']['pages']);
        $this->assertEquals('@seo:title', $raw['section_defaults']['collections']['pages']['sites']['default']['title']);
    }
}
