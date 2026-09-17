<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\Facades\Role;
use Statamic\Facades\User;

class LlmsCpTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->files->delete(public_path('llms.txt'));

        parent::tearDown();
    }

    #[Test]
    public function a_super_user_can_view_disabled_defaults()
    {
        $this->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.llms.edit'))
            ->assertOk()
            ->assertJsonPath('values.enabled', false)
            ->assertJsonPath('values.mode', 'managed')
            ->assertJsonPath('values.title', '{{ config:app:name }}')
            ->assertJsonPath('values.collections', [])
            ->assertJsonPath('values.entries', [])
            ->assertJsonFragment(['label' => 'Articles', 'value' => 'articles'])
            ->assertJsonFragment([
                'label' => 'About — Pages',
                'value' => '62136fa2-9e5c-4c38-a894-a2753f02f5ff',
            ])
            ->assertJsonPath('file.exists', false)
            ->assertJsonPath('file.path', public_path('llms.txt'))
            ->assertJsonPath('liveUrl', 'http://cool-runnings.com/llms.txt');
    }

    #[Test]
    public function saving_enables_the_route_without_creating_a_file()
    {
        $this->actingAs(User::make()->makeSuper()->save())
            ->patchJson(cp_route('seo-pro.llms.update'), $this->payload())
            ->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('file.exists', false);

        $this->assertFileDoesNotExist(public_path('llms.txt'));
        $this->assertTrue((bool) Addon::get('statamic/seo-pro')->settings()->get('llms.sites.default.policy.enabled'));
        $this->get('/llms.txt')->assertOk()->assertContent("# Cool Runnings\n");
    }

    #[Test]
    public function generating_creates_a_managed_file()
    {
        $this->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.llms.generate'), $this->payload())
            ->assertOk()
            ->assertJsonPath('generated', true)
            ->assertJsonPath('changed', true)
            ->assertJsonPath('file.exists', true)
            ->assertJsonPath('file.managed', true);

        $this->assertSame("# Cool Runnings\n", $this->files->get(public_path('llms.txt')));
    }

    #[Test]
    public function selected_collections_and_entries_are_saved_and_previewed()
    {
        $this->actingAs(User::make()->makeSuper()->save())
            ->patchJson(cp_route('seo-pro.llms.update'), $this->payload([
                'collections' => ['articles'],
                'entries' => ['62136fa2-9e5c-4c38-a894-a2753f02f5ff'],
            ]))
            ->assertOk()
            ->assertJsonPath('saved', true)
            ->assertSee('## Articles', false)
            ->assertSee('## Pages', false);

        $settings = Addon::get('statamic/seo-pro')->settings();

        $this->assertSame(['articles'], $settings->get('llms.sites.default.policy.collections'));
        $this->assertSame(
            ['62136fa2-9e5c-4c38-a894-a2753f02f5ff'],
            $settings->get('llms.sites.default.policy.entries'),
        );
    }

    #[Test]
    public function saving_updates_only_a_file_that_seo_pro_manages()
    {
        $user = User::make()->makeSuper()->save();
        $this->actingAs($user)->postJson(cp_route('seo-pro.llms.generate'), $this->payload())->assertOk();

        $this->actingAs($user)
            ->patchJson(cp_route('seo-pro.llms.update'), $this->payload(['title' => 'Updated']))
            ->assertOk()
            ->assertJsonPath('fileChanged', true)
            ->assertJsonPath('file.managed', true);

        $this->assertSame("# Updated\n", $this->files->get(public_path('llms.txt')));
    }

    #[Test]
    public function an_unmanaged_file_is_reported_and_generation_is_refused()
    {
        $legacy = "# Existing manual file\n";
        $this->files->put(public_path('llms.txt'), $legacy);
        $user = User::make()->makeSuper()->save();

        $this->actingAs($user)
            ->getJson(cp_route('seo-pro.llms.edit'))
            ->assertOk()
            ->assertJsonPath('file.exists', true)
            ->assertJsonPath('file.managed', false)
            ->assertJsonPath('file.contents', $legacy);

        $this->actingAs($user)
            ->postJson(cp_route('seo-pro.llms.generate'), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertSame($legacy, $this->files->get(public_path('llms.txt')));
    }

    #[Test]
    public function enabled_managed_mode_requires_a_renderable_h1_title()
    {
        $user = User::make()->makeSuper()->save();

        $this->actingAs($user)
            ->patchJson(cp_route('seo-pro.llms.update'), $this->payload(['title' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');

        config(['statamic.system.view_config_allowlist' => ['app.name']]);
        config(['app.name' => '']);
        $this->actingAs($user)
            ->patchJson(cp_route('seo-pro.llms.update'), $this->payload([
                'title' => '{{ config:app:name }}',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document');
    }

    #[Test]
    public function enabled_custom_mode_requires_an_h1_after_antlers_is_parsed()
    {
        $this->actingAs(User::make()->makeSuper()->save())
            ->patchJson(cp_route('seo-pro.llms.update'), $this->payload([
                'mode' => 'custom',
                'custom_source' => 'No heading',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document');
    }

    #[Test]
    public function users_without_permission_cannot_manage_llms_txt()
    {
        Role::make('editor')->permissions(['access cp'])->save();
        $user = User::make()->assignRole('editor')->save();

        $this->actingAs($user)->get(cp_route('seo-pro.llms.edit'))->assertRedirect('/cp');
        $this->actingAs($user)->patch(cp_route('seo-pro.llms.update'), $this->payload())->assertRedirect('/cp');
        $this->actingAs($user)->post(cp_route('seo-pro.llms.generate'), $this->payload())->assertRedirect('/cp');
        $this->actingAs($user)->post(cp_route('seo-pro.llms.preview'), $this->payload())->assertRedirect('/cp');
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'site' => 'default',
            'enabled' => true,
            'mode' => 'managed',
            'title' => 'Cool Runnings',
            'summary' => '',
            'details' => '',
            'collections' => [],
            'entries' => [],
            'sections' => [],
            'custom_source' => '',
        ], $overrides);
    }
}
