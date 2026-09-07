<?php

namespace Tests\Redirects;

use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Collection;
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
        Collection::make('pages')->save();

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

    #[Test]
    public function source_query_parameter_is_prepopulated()
    {
        Collection::make('pages')->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.redirects.create', ['source' => '/broken-url']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('values.source', '/broken-url')
            );
    }
}
