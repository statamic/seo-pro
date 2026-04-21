<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
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
        Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('https://cool-runnings.com/old-url')
            ->destinationUrl('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.redirects.edit', 'abc'))
            ->assertOk()
            ->assertSee('https://cool-runnings.com/old-url');
    }

    #[Test]
    public function cant_edit_redirect_without_permission()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('https://cool-runnings.com/old-url')
            ->destinationUrl('https://cool-runnings.com/new-url')
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
