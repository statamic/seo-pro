<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class CreateRedirectTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_create_redirect()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.redirects.create'))
            ->assertOk()
            ->assertSee('Create Redirect');
    }

    #[Test]
    public function cant_create_redirect_without_permission()
    {
        Role::make('test')->addPermission('access cp')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->get(cp_route('seo-pro.redirects.create'))
            ->assertRedirect('/cp');
    }
}
