<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Collection;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class EditRedirectTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_edit_redirect()
    {
        Collection::make('pages')->save();

        Facades\Redirect::make()
            ->id('abc')
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.redirects.edit', 'abc'))
            ->assertOk()
            ->assertSee('cool-runnings.com\/old-url', escape: false);
    }

    #[Test]
    public function cant_edit_redirect_without_permission()
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
            ->get(cp_route('seo-pro.redirects.edit', 'abc'))
            ->assertRedirect('/cp');
    }
}
