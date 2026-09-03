<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class DeleteErrorTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_delete_selected_errors()
    {
        $this->createError('abc', '/broken-link');
        $this->createError('def', '/another-broken-link');
        $this->createError('ghi', '/untouched-link');

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.errors.actions.run'), [
                'action' => 'delete_error',
                'selections' => ['abc', 'def'],
                'values' => [],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull(Facades\Error::find('abc'));
        $this->assertNull(Facades\Error::find('def'));
        $this->assertNotNull(Facades\Error::find('ghi'));
    }

    #[Test]
    public function delete_action_is_listed_for_selected_errors()
    {
        $this->createError('abc', '/broken-link');
        $this->createError('def', '/another-broken-link');

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.errors.actions.bulk'), [
                'selections' => ['abc', 'def'],
            ])
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.handle', 'delete_error');
    }

    #[Test]
    public function cant_delete_selected_errors_without_permission()
    {
        $this->createError('abc', '/broken-link');
        $this->createError('def', '/another-broken-link');

        Role::make('test')->addPermission('access cp')->addPermission('view seo redirects')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->postJson(cp_route('seo-pro.errors.actions.run'), [
                'action' => 'delete_error',
                'selections' => ['abc', 'def'],
                'values' => [],
            ])
            ->assertForbidden();

        $this->assertNotNull(Facades\Error::find('abc'));
        $this->assertNotNull(Facades\Error::find('def'));
    }

    #[Test]
    public function delete_action_is_not_listed_without_permission()
    {
        $this->createError('abc', '/broken-link');
        $this->createError('def', '/another-broken-link');

        Role::make('test')->addPermission('access cp')->addPermission('view seo redirects')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->postJson(cp_route('seo-pro.errors.actions.bulk'), [
                'selections' => ['abc', 'def'],
            ])
            ->assertOk()
            ->assertJsonCount(0);
    }

    private function createError(string $id, string $url): void
    {
        Facades\Error::make()
            ->id($id)
            ->url($url)
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();
    }
}
