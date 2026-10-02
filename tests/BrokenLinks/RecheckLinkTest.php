<?php

namespace Tests\BrokenLinks;

use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Role;
use Statamic\Facades\User;
use Statamic\SeoPro\BrokenLinks\CheckExternalLinks;
use Statamic\SeoPro\BrokenLinks\LinkStatus;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class RecheckLinkTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_recheck_selected_links()
    {
        Queue::fake();

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/one')->status(LinkStatus::Broken)->save();
        Facades\ExternalLink::make()->id('def')->url('https://example.com/two')->status(LinkStatus::Broken)->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.broken-links.actions.run'), [
                'action' => 'recheck_link',
                'selections' => ['abc', 'def'],
                'values' => [],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        Queue::assertPushed(CheckExternalLinks::class, 1);
    }

    #[Test]
    public function cant_recheck_selected_links_without_permission()
    {
        Queue::fake();

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/one')->status(LinkStatus::Broken)->save();

        Role::make('test')->addPermission('access cp')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->postJson(cp_route('seo-pro.broken-links.actions.run'), [
                'action' => 'recheck_link',
                'selections' => ['abc'],
                'values' => [],
            ])
            ->assertForbidden();

        Queue::assertNotPushed(CheckExternalLinks::class);
    }
}
