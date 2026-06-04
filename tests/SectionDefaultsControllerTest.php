<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\SectionDefaults\SectionDefaults;

class SectionDefaultsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SectionDefaults::clearCache('collections', 'pages');
        SectionDefaults::clearCache('collections', 'articles');
        SectionDefaults::clearCache('taxonomies', 'topics');
    }

    // -------------------------------------------------------------------------
    // Edit (GET) — collections
    // -------------------------------------------------------------------------

    #[Test]
    public function can_edit_collection_section_defaults()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit');

        $response->assertOk();
        $response->assertJsonStructure([
            'blueprint',
            'initialValues',
            'initialMeta',
            'initialEnabled',
            'initialLocalizations',
            'initialLocalizedFields',
            'initialHasOrigin',
            'initialSite',
            'action',
            'title',
            'configureUrl',
        ]);
    }

    #[Test]
    public function edit_collection_returns_correct_title()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit');

        $response->assertOk();
        $this->assertEquals('Pages SEO', $response->json('title'));
    }

    #[Test]
    public function edit_single_site_returns_null_configure_url()
    {
        // Single-site fixture — no origin configuration needed.
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit');

        $response->assertOk();
        $this->assertNull($response->json('configureUrl'));
    }

    #[Test]
    public function edit_collection_shows_section_as_enabled_by_default()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit');

        $response->assertOk();
        $this->assertTrue($response->json('initialEnabled'));
    }

    #[Test]
    public function edit_collection_shows_section_as_disabled_when_disabled()
    {
        SectionDefaults::disable('collections', 'pages');

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit');

        $response->assertOk();
        $this->assertFalse($response->json('initialEnabled'));
    }

    #[Test]
    public function edit_collection_includes_localizations_array()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit');

        $localizations = $response->json('initialLocalizations');
        $this->assertCount(1, $localizations);
        $this->assertEquals('default', $localizations[0]['handle']);
        $this->assertTrue($localizations[0]['active']);
    }

    #[Test]
    public function edit_collection_includes_saved_values()
    {
        SectionDefaults::in('collections', 'pages', 'default')
            ->set(['title' => '@seo:title'])
            ->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit');

        $response->assertOk();
        // seo_pro_source fieldtype preProcess transforms '@seo:title' to {source: 'field', value: 'title'}.
        $this->assertEquals(['source' => 'field', 'value' => 'title'], $response->json('initialValues.title'));
    }

    // -------------------------------------------------------------------------
    // Edit (GET) — taxonomies
    // -------------------------------------------------------------------------

    #[Test]
    public function can_edit_taxonomy_section_defaults()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson('/cp/seo-pro/section-defaults/taxonomies/topics/edit');

        $response->assertOk();
        $this->assertEquals('Topics SEO', $response->json('title'));
    }

    // -------------------------------------------------------------------------
    // Update (PATCH) — collections
    // -------------------------------------------------------------------------

    #[Test]
    public function can_update_collection_section_defaults()
    {
        // POST the pre-processed format that the Vue frontend sends.
        // seo_pro_source fieldtype: {source: 'field', value: 'title'} → stored as '@seo:title'
        //                           {source: 'custom', value: '...'} → stored as the raw string
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/section-defaults/collections/pages', [
                'title' => ['source' => 'field', 'value' => 'title'],
                'description' => ['source' => 'custom', 'value' => 'My page description'],
                'enabled' => true,
            ])
            ->assertOk();

        SectionDefaults::clearCache('collections', 'pages');

        $localized = SectionDefaults::in('collections', 'pages', 'default');
        $this->assertTrue($localized->isEnabled());
        $this->assertEquals('@seo:title', $localized->all()['title']);
        $this->assertEquals('My page description', $localized->all()['description']);
    }

    #[Test]
    public function can_disable_collection_via_update()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/section-defaults/collections/pages', [
                'enabled' => false,
            ])
            ->assertOk();

        SectionDefaults::clearCache('collections', 'pages');

        $this->assertFalse(SectionDefaults::isEnabled('collections', 'pages'));
    }

    #[Test]
    public function disabling_a_collection_via_update_stores_false_in_settings()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/section-defaults/collections/pages', [
                'enabled' => false,
            ])
            ->assertOk();

        $raw = Addon::get('statamic/seo-pro')->settings()->raw();
        $this->assertFalse($raw['section_defaults']['collections']['pages']);
    }

    #[Test]
    public function can_update_taxonomy_section_defaults()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/section-defaults/taxonomies/topics', [
                'title' => ['source' => 'field', 'value' => 'title'],
                'enabled' => true,
            ])
            ->assertOk();

        SectionDefaults::clearCache('taxonomies', 'topics');

        $localized = SectionDefaults::in('taxonomies', 'topics', 'default');
        $this->assertEquals('@seo:title', $localized->all()['title']);
    }

    #[Test]
    public function enabled_field_is_not_stored_in_section_defaults_values()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson('/cp/seo-pro/section-defaults/collections/pages', [
                'title' => ['source' => 'field', 'value' => 'title'],
                'enabled' => true,
            ])
            ->assertOk();

        SectionDefaults::clearCache('collections', 'pages');

        $localized = SectionDefaults::in('collections', 'pages', 'default');
        $this->assertArrayNotHasKey('enabled', $localized->all());
    }

    // -------------------------------------------------------------------------
    // Permissions
    // -------------------------------------------------------------------------

    #[Test]
    public function cannot_edit_collection_defaults_without_permission()
    {
        Role::make('editor')->permissions(['access cp'])->save();

        $this
            ->actingAs(User::make()->assignRole('editor')->save())
            ->getJson('/cp/seo-pro/section-defaults/collections/pages/edit')
            ->assertForbidden();
    }

    #[Test]
    public function cannot_update_collection_defaults_without_permission()
    {
        Role::make('editor')->permissions(['access cp'])->save();

        $this
            ->actingAs(User::make()->assignRole('editor')->save())
            ->patchJson('/cp/seo-pro/section-defaults/collections/pages', [
                'title' => '@seo:title',
                'enabled' => true,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function cannot_edit_taxonomy_defaults_without_permission()
    {
        Role::make('editor')->permissions(['access cp'])->save();

        $this
            ->actingAs(User::make()->assignRole('editor')->save())
            ->getJson('/cp/seo-pro/section-defaults/taxonomies/topics/edit')
            ->assertForbidden();
    }
}
