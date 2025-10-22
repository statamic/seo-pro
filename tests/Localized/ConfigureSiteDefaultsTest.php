<?php

namespace Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Tests\Localized\LocalizedTestCase;

class ConfigureSiteDefaultsTest extends LocalizedTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Addon::get('statamic/seo-pro')->settings()->set('site_defaults_sites', [
            'default' => null,
            'french' => null,
            'italian' => 'british',
            'british' => 'default',
        ])->save();
    }

    #[Test]
    public function can_edit_site_defaults_configuration()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get('/cp/seo-pro/site-defaults/configure')
            ->assertOk()
            ->assertJson([
                'sites' => [
                    ['name' => 'English', 'handle' => 'default', 'origin' => null],
                    ['name' => 'French', 'handle' => 'french', 'origin' => null],
                    ['name' => 'Italian', 'handle' => 'italian', 'origin' => 'british'],
                    ['name' => 'British', 'handle' => 'british', 'origin' => 'default'],
                ],
            ]);
    }

    #[Test]
    public function can_update_site_defaults_configuration()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patch('/cp/seo-pro/site-defaults/configure', [
                'sites' => [
                    ['name' => 'English', 'handle' => 'default', 'origin' => null],
                    ['name' => 'French', 'handle' => 'french', 'origin' => 'default'], // Added
                    ['name' => 'Italian', 'handle' => 'italian', 'origin' => null], // Removed
                    ['name' => 'British', 'handle' => 'british', 'origin' => 'default'],
                ],
            ])
            ->assertOk();

        $this->assertEquals([
            'default' => null,
            'french' => 'default',
            'italian' => null,
            'british' => 'default',
        ], Addon::get('statamic/seo-pro')->settings()->get('site_defaults_sites'));
    }

    #[Test]
    public function can_update_site_defaults_configuration_when_every_site_has_an_origin()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patch('/cp/seo-pro/site-defaults/configure', [
                'sites' => [
                    ['name' => 'English', 'handle' => 'default', 'origin' => 'french'],
                    ['name' => 'French', 'handle' => 'french', 'origin' => 'default'],
                    ['name' => 'Italian', 'handle' => 'italian', 'origin' => 'british'],
                    ['name' => 'British', 'handle' => 'british', 'origin' => 'default'],
                ],
            ])
            ->assertSessionHasErrors('sites');

        $this->assertEquals([
            'default' => null,
            'french' => null,
            'italian' => 'british',
            'british' => 'default',
        ], Addon::get('statamic/seo-pro')->settings()->get('site_defaults_sites'));
    }

    #[Test]
    public function cant_edit_or_update_site_defaults_configuration_without_permission()
    {
        Site::setSites([
            'default' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://cool-runnings.com'],
        ]);

        Role::make('editor')->permissions(['access cp'])->save();

        $this
            ->actingAs(User::make()->assignRole('editor')->save())
            ->get('/cp/seo-pro/site-defaults/configure')
            ->assertRedirect('/cp');

        $this
            ->actingAs(User::make()->assignRole('editor')->save())
            ->patch('/cp/seo-pro/site-defaults/configure', [
                'sites' => [
                    ['name' => 'English', 'handle' => 'default', 'origin' => null],
                ],
            ])
            ->assertRedirect('/cp');
    }

    #[Test]
    public function cant_edit_or_update_site_defaults_configuration_unless_multisite_is_enabled()
    {
        Site::setSites([
            'default' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://cool-runnings.com'],
        ]);

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get('/cp/seo-pro/site-defaults/configure')
            ->assertNotFound();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patch('/cp/seo-pro/site-defaults/configure', [
                'sites' => [
                    ['name' => 'English', 'handle' => 'default', 'origin' => null],
                ],
            ])
            ->assertNotFound();
    }
}
