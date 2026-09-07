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
                'source' => '/old-url',
                'destination' => '/new-url',
                'response_code' => 302,
                'enabled' => true,
            ])
            ->assertOk();

        $redirect = Facades\Redirect::query()->where('source', '/old-url')->first();

        $this->assertNotNull($redirect);
        $this->assertEquals('/old-url', $redirect->source());
        $this->assertEquals('/new-url', $redirect->destination());
        $this->assertEquals(302, $redirect->responseCode());
        $this->assertTrue($redirect->enabled());
    }

    #[Test]
    public function store_returns_redirect_to_edit_url()
    {
        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source' => '/old-url',
                'destination' => '/new-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertOk();

        $redirect = Facades\Redirect::query()->where('source', '/old-url')->first();

        $response->assertJson(['redirect' => $redirect->editUrl()]);
    }

    #[Test]
    public function cant_store_redirect_without_permission()
    {
        Role::make('test')->addPermission('access cp')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source' => '/old-url',
                'destination' => '/new-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertRedirect('/cp');

        $this->assertNull(
            Facades\Redirect::query()->where('source', '/old-url')->first()
        );
    }

    #[Test]
    public function source_is_required()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source' => '',
                'destination' => '/new-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('source');
    }

    #[Test]
    public function destination_is_required()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source' => '/old-url',
                'destination' => '',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('destination');
    }

    #[Test]
    public function response_code_is_required()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source' => '/old-url',
                'destination' => '/new-url',
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
                'source' => '/old-url',
                'destination' => '/blog/post/2026',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertOk();

        $redirect = Facades\Redirect::query()->where('source', '/old-url')->first();

        $this->assertNotNull($redirect);
        $this->assertEquals('/old-url', $redirect->source());
        $this->assertEquals('/blog/post/2026', $redirect->destination());
    }

    #[Test]
    public function source_must_be_a_valid_path()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source' => 'not a valid url',
                'destination' => '/new-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('source');
    }

    #[Test]
    public function source_must_be_unique()
    {
        Facades\Redirect::make()
            ->id('existing')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source' => '/old-url',
                'destination' => '/another-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('source');
    }

    #[Test]
    public function can_store_redirect_with_description()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->post(cp_route('seo-pro.redirects.store'), [
                'source' => '/old-url',
                'destination' => '/new-url',
                'response_code' => 301,
                'enabled' => true,
                'description' => 'Keep this redirect for the old campaign.',
            ])
            ->assertOk();

        $redirect = Facades\Redirect::query()->where('source', '/old-url')->first();

        $this->assertEquals('Keep this redirect for the old campaign.', $redirect->get('description'));
    }
}
