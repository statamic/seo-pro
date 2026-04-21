<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class StoreRedirectTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_store_redirect()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source_url' => 'https://cool-runnings.com/old-url',
                'destination_url' => 'https://cool-runnings.com/new-url',
                'response_code' => 302,
                'enabled' => true,
            ])
            ->assertOk();

        $redirect = Facades\Redirect::query()->where('source_url', 'https://cool-runnings.com/old-url')->first();

        $this->assertNotNull($redirect);
        $this->assertEquals('https://cool-runnings.com/old-url', $redirect->sourceUrl());
        $this->assertEquals('https://cool-runnings.com/new-url', $redirect->destinationUrl());
        $this->assertEquals(302, $redirect->responseCode());
        $this->assertTrue($redirect->enabled());
    }

    #[Test]
    public function store_returns_redirect_to_edit_url()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source_url' => 'https://cool-runnings.com/old-url',
                'destination_url' => 'https://cool-runnings.com/new-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertOk();

        $redirect = Facades\Redirect::query()->where('source_url', 'https://cool-runnings.com/old-url')->first();

        $response->assertJson(['redirect' => $redirect->editUrl()]);
    }

    #[Test]
    public function cant_store_redirect_without_permission()
    {
        Role::make('test')->addPermission('access cp')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source_url' => 'https://cool-runnings.com/old-url',
                'destination_url' => 'https://cool-runnings.com/new-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertRedirect('/cp');

        $this->assertNull(
            Facades\Redirect::query()->where('source_url', 'https://cool-runnings.com/old-url')->first()
        );
    }

    #[Test]
    public function source_url_is_required()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source_url' => '',
                'destination_url' => 'https://cool-runnings.com/new-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('source_url');
    }

    #[Test]
    public function destination_url_is_required()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source_url' => 'https://cool-runnings.com/old-url',
                'destination_url' => '',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('destination_url');
    }

    #[Test]
    public function response_code_is_required()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source_url' => 'https://cool-runnings.com/old-url',
                'destination_url' => 'https://cool-runnings.com/new-url',
                'response_code' => '',
                'enabled' => true,
            ])
            ->assertSessionHasErrors('response_code');
    }

    #[Test]
    public function can_store_redirect_with_path_urls()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source_url' => '/old-url',
                'destination_url' => '/blog/post/2026',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertOk();

        $redirect = Facades\Redirect::query()->where('source_url', '/old-url')->first();

        $this->assertNotNull($redirect);
        $this->assertEquals('/old-url', $redirect->sourceUrl());
        $this->assertEquals('/blog/post/2026', $redirect->destinationUrl());
    }

    #[Test]
    public function source_url_must_be_a_valid_url_or_path()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source_url' => 'not a valid url',
                'destination_url' => 'https://cool-runnings.com/new-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('source_url');
    }

    #[Test]
    public function destination_url_must_be_a_valid_url_or_path()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source_url' => 'https://cool-runnings.com/old-url',
                'destination_url' => 'not a valid url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('destination_url');
    }

    #[Test]
    public function source_url_must_be_unique()
    {
        Facades\Redirect::make()
            ->id('existing')
            ->sourceUrl('https://cool-runnings.com/old-url')
            ->destinationUrl('https://cool-runnings.com/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source_url' => 'https://cool-runnings.com/old-url',
                'destination_url' => 'https://cool-runnings.com/another-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('source_url');
    }
}
