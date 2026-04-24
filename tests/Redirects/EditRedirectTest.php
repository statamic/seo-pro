<?php

namespace Tests\Redirects;

use Inertia\Testing\AssertableInertia;
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
    public function can_view_redirect()
    {
        Collection::make('pages')->save();

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.redirects.edit', 'abc'))
            ->assertOk()
            ->assertSee('old-url', escape: false);
    }

    #[Test]
    public function can_view_redirect_without_edit_permission()
    {
        Collection::make('pages')->save();

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        Role::make('test')->addPermission('access cp')->addPermission('view seo redirects')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->get(cp_route('seo-pro.redirects.edit', 'abc'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true));
    }

    #[Test]
    public function cant_view_redirect_without_permission()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
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
