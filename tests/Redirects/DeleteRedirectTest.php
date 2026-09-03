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
    public function can_delete_selected_redirects()
    {
        $this->createRedirect('abc', '/old-url');
        $this->createRedirect('def', '/another-old-url');
        $this->createRedirect('ghi', '/untouched-url');

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.actions.run'), [
                'action' => 'delete_redirect',
                'selections' => ['abc', 'def'],
                'values' => [],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(Facades\Redirect::find('abc'));
        $this->assertNull(Facades\Redirect::find('def'));
        $this->assertNotNull(Facades\Redirect::find('ghi'));
    }

    #[Test]
    public function delete_action_is_listed_for_selected_redirects()
    {
        $this->createRedirect('abc', '/old-url');
        $this->createRedirect('def', '/another-old-url');

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.redirects.actions.bulk'), [
                'selections' => ['abc', 'def'],
            ])
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.handle', 'delete_redirect');
    }

    #[Test]
    public function cant_delete_selected_redirects_without_permission()
    {
        $this->createRedirect('abc', '/old-url');
        $this->createRedirect('def', '/another-old-url');

        Role::make('test')->addPermission('access cp')->addPermission('view seo redirects')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->postJson(cp_route('seo-pro.redirects.actions.run'), [
                'action' => 'delete_redirect',
                'selections' => ['abc', 'def'],
                'values' => [],
            ])
            ->assertForbidden();

        $this->assertNotNull(Facades\Redirect::find('abc'));
        $this->assertNotNull(Facades\Redirect::find('def'));
    }

    #[Test]
    public function delete_action_is_not_listed_without_permission()
    {
        $this->createRedirect('abc', '/old-url');
        $this->createRedirect('def', '/another-old-url');

        Role::make('test')->addPermission('access cp')->addPermission('view seo redirects')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->postJson(cp_route('seo-pro.redirects.actions.bulk'), [
                'selections' => ['abc', 'def'],
            ])
            ->assertOk()
            ->assertJsonCount(0);
    }

    private function createRedirect(string $id, string $source): void
    {
        Facades\Redirect::make()
            ->id($id)
            ->source($source)
            ->destination('/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();
    }
}
