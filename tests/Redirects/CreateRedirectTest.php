<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Collection;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades;
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
    public function it_saves_redirect_description_when_creating()
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
