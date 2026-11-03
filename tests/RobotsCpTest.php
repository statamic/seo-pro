<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Addon;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\Robots\RobotsTxtGenerator;

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
            ->assertJsonPath('generateUrl', cp_route('seo-pro.robots.generate'))
            ->assertJsonPath('sitemapUrlsAreEnvironmentDependent', true)
            ->assertJsonPath('liveUrl', 'http://cool-runnings.com/robots.txt');
    }

    #[Test]
    public function saving_persists_the_settings_without_writing_the_file()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson(cp_route('seo-pro.robots.update'), $this->validPayload([
                'disallow' => ['/private/'],
            ]))
            ->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('file.exists', false);

        $this->assertFileDoesNotExist(public_path('robots.txt'));
        $this->assertSame(['/private/'], Addon::get('statamic/seo-pro')->settings()->get('robots.policy.disallow'));
        $this->assertNull(Addon::get('statamic/seo-pro')->settings()->get('robots.generated'));
    }

    #[Test]
    public function generating_writes_the_file_and_then_persists_the_settings()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.robots.generate'), $this->validPayload([
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
            ->assertJsonPath('changed', true)
            ->assertJsonPath('file.exists', true)
            ->assertJsonPath('file.managed', true)
            ->assertJsonPath('file.outdated', false);

        $this->assertFileExists(public_path('robots.txt'));
        $this->assertStringContainsString('User-agent: GPTBot', $response->json('preview'));
        $this->assertSame('disallow', Addon::get('statamic/seo-pro')->settings()->get('robots.policy.ai.training'));
    }

    #[Test]
    public function generating_an_up_to_date_file_reports_no_change()
    {
        $user = User::make()->makeSuper()->save();

        $this
            ->actingAs($user)
            ->postJson(cp_route('seo-pro.robots.generate'), $this->validPayload())
            ->assertOk()
            ->assertJsonPath('changed', true);

        $this
            ->actingAs($user)
            ->postJson(cp_route('seo-pro.robots.generate'), $this->validPayload())
            ->assertOk()
            ->assertJsonPath('generated', true)
            ->assertJsonPath('changed', false)
            ->assertJsonPath('file.managed', true)
            ->assertJsonPath('file.outdated', false);
    }

    #[Test]
    public function saving_a_changed_policy_marks_the_generated_file_as_outdated()
    {
        app(RobotsTxtGenerator::class)->generate();
        $contents = $this->files->get(public_path('robots.txt'));

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson(cp_route('seo-pro.robots.update'), $this->validPayload([
                'disallow' => ['/private/'],
            ]))
            ->assertOk()
            ->assertJsonPath('file.exists', true)
            ->assertJsonPath('file.managed', true)
            ->assertJsonPath('file.outdated', true);

        $this->assertSame($contents, $this->files->get(public_path('robots.txt')));
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
    public function custom_sitemap_urls_must_be_absolute_http_urls()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson(cp_route('seo-pro.robots.update'), $this->validPayload([
                'sitemap_mode' => 'custom',
                'sitemap_urls' => ['/sitemap.xml'],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sitemap_urls.0');
    }

    #[Test]
    public function custom_sitemap_mode_requires_at_least_one_url_when_enabled()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patchJson(cp_route('seo-pro.robots.update'), $this->validPayload([
                'sitemap_mode' => 'custom',
                'sitemap_urls' => [],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sitemap_urls');
    }

    #[Test]
    public function users_without_permission_cannot_manage_robots_settings()
    {
        Role::make('editor')->permissions(['access cp'])->save();
        $user = User::make()->assignRole('editor')->save();

        $this->actingAs($user)->get(cp_route('seo-pro.robots.edit'))->assertRedirect('/cp');
        $this->actingAs($user)->patch(cp_route('seo-pro.robots.update'), $this->validPayload())->assertRedirect('/cp');
        $this->actingAs($user)->post(cp_route('seo-pro.robots.generate'), $this->validPayload())->assertRedirect('/cp');
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
            ->assertJsonPath('file.importable', true)
            ->assertJsonPath('file.contents', "User-agent: *\nDisallow: /legacy/");
    }

    #[Test]
    public function an_oversized_existing_file_is_not_loaded_for_import()
    {
        $this->files->put(public_path('robots.txt'), str_repeat('x', RobotsTxtGenerator::MAX_IMPORT_BYTES + 1));

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.robots.edit'))
            ->assertOk()
            ->assertJsonPath('file.exists', true)
            ->assertJsonPath('file.managed', false)
            ->assertJsonPath('file.importable', false)
            ->assertJsonPath('file.import_issue', 'too_large')
            ->assertJsonPath('file.contents', null);
    }

    #[Test]
    public function a_non_utf8_existing_file_is_not_loaded_for_import()
    {
        $this->files->put(public_path('robots.txt'), "User-agent: *\n# \xC3\x28\n");

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.robots.edit'))
            ->assertOk()
            ->assertJsonPath('file.exists', true)
            ->assertJsonPath('file.managed', false)
            ->assertJsonPath('file.importable', false)
            ->assertJsonPath('file.import_issue', 'invalid_utf8')
            ->assertJsonPath('file.contents', null);
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
            'sitemap_mode' => 'automatic',
            'sitemap_urls' => [],
            'custom_source' => '',
        ], $overrides);
    }
}
