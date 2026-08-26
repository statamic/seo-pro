<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\Facades\Role;
use Statamic\Facades\User;

class RobotsCpTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->files->delete(public_path('robots.txt'));

        parent::tearDown();
    }

    #[Test]
    public function a_super_user_can_view_robots_settings_as_json()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.robots.edit'))
            ->assertOk()
            ->assertJsonPath('values.mode', 'managed')
            ->assertJsonPath('values.ai.training', 'neutral')
            ->assertJsonPath('file.exists', false)
            ->assertJsonPath('file.path', public_path('robots.txt'))
            ->assertJsonPath('liveUrl', 'http://cool-runnings.com/robots.txt');
    }

    #[Test]
    public function generating_writes_the_file_and_then_persists_the_settings()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson(cp_route('seo-pro.robots.update'), $this->validPayload([
                'preset' => 'discoverable',
                'ai' => [
                    'search' => 'allow',
                    'agent' => 'allow',
                    'training' => 'disallow',
                ],
                'content_signals' => [
                    'search' => 'yes',
                    'ai_input' => 'yes',
                    'ai_train' => 'no',
                    'use' => 'reference',
                ],
            ]))
            ->assertOk()
            ->assertJsonPath('generated', true)
            ->assertJsonPath('file.exists', true)
            ->assertJsonPath('file.managed', true);

        $this->assertFileExists(public_path('robots.txt'));
        $this->assertStringContainsString('User-agent: GPTBot', $response->json('preview'));
        $this->assertSame('disallow', Addon::get('statamic/seo-pro')->settings()->get('robots.policy.ai.training'));
    }

    #[Test]
    public function preview_does_not_persist_settings_or_write_a_file()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.robots.preview'), $this->validPayload([
                'mode' => 'custom',
                'custom_source' => "User-agent: *\nDisallow: /preview/",
            ]))
            ->assertOk()
            ->assertJsonPath('preview', "User-agent: *\nDisallow: /preview/\n");

        $this->assertFileDoesNotExist(public_path('robots.txt'));
        $this->assertNull(Addon::get('statamic/seo-pro')->settings()->get('robots'));
    }

    #[Test]
    public function paths_must_start_with_a_slash()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson(cp_route('seo-pro.robots.update'), $this->validPayload([
                'disallow' => ['private'],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('disallow.0');

        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    #[Test]
    public function managed_paths_cannot_inject_additional_directives()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson(cp_route('seo-pro.robots.update'), $this->validPayload([
                'disallow' => ["/private/\nAllow: /private/public/"],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('disallow.0');
    }

    #[Test]
    public function users_without_permission_cannot_manage_robots_settings()
    {
        Role::make('editor')->permissions(['access cp'])->save();
        $user = User::make()->assignRole('editor')->save();

        $this->actingAs($user)->get(cp_route('seo-pro.robots.edit'))->assertRedirect('/cp');
        $this->actingAs($user)->patch(cp_route('seo-pro.robots.update'), $this->validPayload())->assertRedirect('/cp');
        $this->actingAs($user)->post(cp_route('seo-pro.robots.preview'), $this->validPayload())->assertRedirect('/cp');
    }

    #[Test]
    public function it_reports_an_existing_unmanaged_file_for_import()
    {
        $this->files->put(public_path('robots.txt'), "User-agent: *\nDisallow: /legacy/");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.robots.edit'))
            ->assertOk()
            ->assertJsonPath('file.exists', true)
            ->assertJsonPath('file.managed', false)
            ->assertJsonPath('file.contents', "User-agent: *\nDisallow: /legacy/");
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'mode' => 'managed',
            'preset' => 'neutral',
            'allow' => ['/'],
            'disallow' => [],
            'ai' => [
                'search' => 'neutral',
                'agent' => 'neutral',
                'training' => 'neutral',
            ],
            'content_signals' => [
                'search' => null,
                'ai_input' => null,
                'ai_train' => null,
                'use' => null,
            ],
            'include_sitemap' => true,
            'custom_source' => '',
        ], $overrides);
    }
}
