<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class DeleteRedirectTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_delete_redirect()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this->assertNotNull(Facades\Redirect::find('abc'));

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->delete(cp_route('seo-pro.redirects.destroy', 'abc'))
            ->assertOk();

        $this->assertNull(Facades\Redirect::find('abc'));
    }

    #[Test]
    public function cant_delete_redirect_without_permission()
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
            ->delete(cp_route('seo-pro.redirects.destroy', 'abc'))
            ->assertRedirect('/cp');

        $this->assertNotNull(Facades\Redirect::find('abc'));
    }
}
