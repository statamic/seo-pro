<?php

namespace Tests\DeadLinks;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\Scope;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades\DeadLink;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ViewDeadLinksTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_view_dead_links_index()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.dead-links.index'))
            ->assertOk();
    }

    #[Test]
    public function cant_view_dead_links_without_permission()
    {
        Role::make('test')->addPermission('access cp')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->get(cp_route('seo-pro.dead-links.index'))
            ->assertRedirect('/cp');
    }

    #[Test]
    public function can_view_dead_links_as_json()
    {
        DeadLink::make()->id('abc')->url('https://example.com/broken')->status('failing')->save();
        DeadLink::make()->id('def')->url('https://example.com/fine')->status('ok')->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.dead-links.index'))
            ->assertOk();

        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function dead_links_are_sorted_by_consecutive_failures_descending_by_default()
    {
        DeadLink::make()->id('low')->url('https://example.com/low')->status('failing')->consecutiveFailures(1)->save();
        DeadLink::make()->id('high')->url('https://example.com/high')->status('failing')->consecutiveFailures(5)->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.dead-links.index'))
            ->assertOk();

        $data = $response->json('data');

        $this->assertEquals('high', $data[0]['id']);
        $this->assertEquals('low', $data[1]['id']);
    }

    #[Test]
    public function dead_links_can_be_searched_by_url()
    {
        DeadLink::make()->id('abc')->url('https://example.com/broken')->save();
        DeadLink::make()->id('def')->url('https://example.com/fine')->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.dead-links.index', ['search' => 'broken']))
            ->assertOk();

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('abc', $data[0]['id']);
    }

    #[Test]
    public function dead_links_can_be_filtered_by_status()
    {
        DeadLink::make()->id('broken')->url('https://example.com/broken')->status('failing')->save();
        DeadLink::make()->id('fine')->url('https://example.com/fine')->status('ok')->save();

        // The real Listing component sends `filters` as a single base64-encoded
        // JSON string (see Statamic\Http\Requests\FilteredRequest), not nested
        // query params.
        $filters = base64_encode(json_encode(['dead_link_status' => ['status' => 'failing']]));

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.dead-links.index', ['filters' => $filters]))
            ->assertOk();

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('broken', $data[0]['id']);
        $this->assertEquals(['dead_link_status' => 'Failing'], $response->json('meta.activeFilterBadges'));
    }

    #[Test]
    public function the_status_filter_is_offered_for_the_dead_links_listing()
    {
        $filters = Scope::filters('dead-links');

        $this->assertTrue($filters->contains(fn ($filter) => $filter->handle() === 'dead_link_status'));
    }

    #[Test]
    public function a_super_user_can_recheck_all_links()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.dead-links.recheck-all'))
            ->assertOk();
    }

    #[Test]
    public function a_user_without_the_manage_permission_cannot_recheck_all_links()
    {
        Role::make('test')->addPermission('access cp')->addPermission('view seo dead links')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->postJson(cp_route('seo-pro.dead-links.recheck-all'))
            ->assertForbidden();
    }
}
