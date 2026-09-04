<?php

namespace Tests\Localized;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\User;

class LlmsCpTest extends LocalizedTestCase
{
    #[Test]
    public function users_can_only_manage_llms_txt_for_authorized_sites()
    {
        $user = $this->makeRestrictedUser();

        $this->actingAs($user)
            ->getJson(cp_route('seo-pro.llms.edit').'?site=default')
            ->assertOk()
            ->assertJsonCount(1, 'sites')
            ->assertJsonPath('sites.0.handle', 'default');

        $this->actingAs($user)
            ->getJson(cp_route('seo-pro.llms.edit').'?site=french')
            ->assertForbidden();

        $this->actingAs($user)
            ->patchJson(cp_route('seo-pro.llms.update'), $this->payload(['site' => 'french']))
            ->assertForbidden();
    }

    #[Test]
    public function content_options_are_limited_to_entries_the_user_can_view()
    {
        $response = $this->actingAs($this->makeRestrictedUser())
            ->getJson(cp_route('seo-pro.llms.edit').'?site=default')
            ->assertOk();

        $response->assertJsonFragment(['label' => 'Pages', 'value' => 'pages']);
        $response->assertJsonMissing(['label' => 'Articles', 'value' => 'articles']);
        $response->assertJsonFragment([
            'label' => 'About — Pages',
            'value' => '62136fa2-9e5c-4c38-a894-a2753f02f5ff',
        ]);
        $response->assertJsonMissing([
            'label' => 'The Magic Happens at 7 1/2 Pumps — Articles',
            'value' => 'af43e0fb-a338-4433-b60a-3bed773be341',
        ]);
    }

    #[Test]
    public function inaccessible_content_cannot_be_selected_using_a_crafted_request()
    {
        $this->actingAs($this->makeRestrictedUser())
            ->patchJson(cp_route('seo-pro.llms.update'), $this->payload([
                'collections' => ['articles'],
                'entries' => ['af43e0fb-a338-4433-b60a-3bed773be341'],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['collections.0', 'entries.0']);
    }

    private function makeRestrictedUser()
    {
        Role::make('llms-editor')->permissions([
            'access cp',
            'edit seo robots',
            'access default site',
            'view pages entries',
        ])->save();

        return User::make()->assignRole('llms-editor')->save();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
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
