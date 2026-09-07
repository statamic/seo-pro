<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ViewErrorsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_view_errors_index()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.errors.index'))
            ->assertOk();
    }

    #[Test]
    public function cant_view_errors_without_permission()
    {
        Role::make('test')->addPermission('access cp')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->get(cp_route('seo-pro.errors.index'))
            ->assertRedirect('/cp');
    }

    #[Test]
    public function can_view_errors_as_json()
    {
        Facades\Error::make()
            ->id('abc')
            ->url('/broken-link')
            ->hits(5)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();

        Facades\Error::make()
            ->id('def')
            ->url('/another-broken-link')
            ->hits(2)
            ->lastHitAt('2026-04-20 10:00:00')
            ->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.errors.index'))
            ->assertOk();

        $data = $response->json('data');

        $this->assertCount(2, $data);
    }

    #[Test]
    public function errors_are_sorted_by_last_hit_at_descending_by_default()
    {
        Facades\Error::make()
            ->id('older')
            ->url('/older-page')
            ->hits(1)
            ->lastHitAt('2026-04-19 12:00:00')
            ->save();

        Facades\Error::make()
            ->id('newer')
            ->url('/newer-page')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.errors.index'))
            ->assertOk();

        $data = $response->json('data');

        $this->assertEquals('newer', $data[0]['id']);
        $this->assertEquals('older', $data[1]['id']);
    }

    #[Test]
    public function errors_can_be_searched()
    {
        Facades\Error::make()
            ->id('abc')
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();

        Facades\Error::make()
            ->id('def')
            ->url('/another-page')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.errors.index', ['search' => 'broken']))
            ->assertOk();

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('abc', $data[0]['id']);
    }

    #[Test]
    public function listed_error_includes_create_redirect_url()
    {
        Facades\Error::make()
            ->id('abc')
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.errors.index'))
            ->assertOk();

        $data = $response->json('data');

        $this->assertArrayHasKey('create_redirect_url', $data[0]);
        $this->assertStringContainsString('source=%2Fbroken-link', $data[0]['create_redirect_url']);
    }
}
