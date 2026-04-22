<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class UpdateRedirectTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_update_redirect()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patch(cp_route('seo-pro.redirects.update', 'abc'), [
                'source' => 'https://cool-runnings.com/updated-old-url',
                'destination' => 'https://cool-runnings.com/updated-new-url',
                'response_code' => 301,
                'enabled' => false,
            ])
            ->assertOk();

        $redirect = Facades\Redirect::find('abc');

        $this->assertEquals('https://cool-runnings.com/updated-old-url', $redirect->source());
        $this->assertEquals('https://cool-runnings.com/updated-new-url', $redirect->destination());
        $this->assertEquals(301, $redirect->responseCode());
        $this->assertFalse($redirect->enabled());
    }

    #[Test]
    public function cant_update_redirect_without_permission()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        Role::make('test')->addPermission('access cp')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->patch(cp_route('seo-pro.redirects.update', 'abc'), [
                'source' => 'https://cool-runnings.com/updated-old-url',
                'destination' => 'https://cool-runnings.com/updated-new-url',
                'response_code' => 301,
                'enabled' => false,
            ])
            ->assertRedirect('/cp');

        $redirect = Facades\Redirect::find('abc');

        $this->assertEquals('https://cool-runnings.com/old-url', $redirect->source());
    }

    #[Test]
    public function source_is_required_when_updating()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patch(cp_route('seo-pro.redirects.update', 'abc'), [
                'source' => '',
                'destination' => 'https://cool-runnings.com/new-url',
                'response_code' => 302,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('source');
    }

    #[Test]
    public function destination_is_required_when_updating()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patch(cp_route('seo-pro.redirects.update', 'abc'), [
                'source' => 'https://cool-runnings.com/old-url',
                'destination' => '',
                'response_code' => 302,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('destination');
    }

    #[Test]
    public function response_code_is_required_when_updating()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patch(cp_route('seo-pro.redirects.update', 'abc'), [
                'source' => 'https://cool-runnings.com/old-url',
                'destination' => 'https://cool-runnings.com/new-url',
                'response_code' => '',
                'enabled' => true,
            ])
            ->assertSessionHasErrors('response_code');
    }

    #[Test]
    public function source_must_be_a_valid_url_or_path_when_updating()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patch(cp_route('seo-pro.redirects.update', 'abc'), [
                'source' => 'not a valid url',
                'destination' => 'https://cool-runnings.com/new-url',
                'response_code' => 302,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('source');
    }

    #[Test]
    public function source_must_be_unique_when_updating()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        Facades\Redirect::make()
            ->id('def')
            ->source('https://cool-runnings.com/another-old-url')
            ->destination('https://cool-runnings.com/another-new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patch(cp_route('seo-pro.redirects.update', 'abc'), [
                'source' => 'https://cool-runnings.com/another-old-url',
                'destination' => 'https://cool-runnings.com/new-url',
                'response_code' => 302,
                'enabled' => true,
            ])
            ->assertSessionHasErrors('source');
    }

    #[Test]
    public function can_update_redirect_without_changing_source()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->patch(cp_route('seo-pro.redirects.update', 'abc'), [
                'source' => 'https://cool-runnings.com/old-url',
                'destination' => 'https://cool-runnings.com/updated-new-url',
                'response_code' => 301,
                'enabled' => true,
            ])
            ->assertOk();

        $redirect = Facades\Redirect::find('abc');

        $this->assertEquals('https://cool-runnings.com/old-url', $redirect->source());
        $this->assertEquals('https://cool-runnings.com/updated-new-url', $redirect->destination());
    }
}
