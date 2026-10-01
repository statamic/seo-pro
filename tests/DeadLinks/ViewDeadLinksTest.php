<?php

namespace Tests\DeadLinks;

use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Role;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Statamic\SeoPro\DeadLinks\ContentScanner;
use Statamic\SeoPro\Facades\DeadLink;
use Statamic\SeoPro\Jobs\CheckDeadLinksJob;
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
    public function the_seo_pro_tools_page_links_to_dead_links_when_permitted()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canViewDeadLinks', true));
    }

    #[Test]
    public function the_seo_pro_tools_page_hides_dead_links_without_permission()
    {
        Role::make('test')->addPermission('access cp')->addPermission('view seo reports')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->get(cp_route('seo-pro.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canViewDeadLinks', false));
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
    public function only_dead_links_in_authorized_sites_are_listed()
    {
        $this->setSites();

        DeadLink::make()->id('english')->site('default')->url('https://example.com/english')->save();
        DeadLink::make()->id('french')->site('fr')->url('https://example.com/french')->save();

        Role::make('test')
            ->addPermission('access cp')
            ->addPermission('view seo dead links')
            ->addPermission('access fr site')
            ->save();

        $response = $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->getJson(cp_route('seo-pro.dead-links.index'))
            ->assertOk();

        $this->assertEquals(['french'], collect($response->json('data'))->pluck('id')->all());
    }

    #[Test]
    public function references_include_edit_urls()
    {
        Collection::make('blog')->save();
        Taxonomy::make('tags')->save();

        $entry = tap(Entry::make()->collection('blog')->slug('hello-world'))->save();
        $term = tap(Term::make()->taxonomy('tags')->slug('news')->data([]))->save();
        $globalSet = tap(GlobalSet::make('footer'))->save();
        $globalSet->makeLocalization('default')->save();

        DeadLink::make()->id('abc')->url('https://example.com/broken')->references([
            ['subject_type' => 'entry', 'subject_id' => $entry->id(), 'site' => 'default', 'field_path' => 'body', 'title' => 'Hello World'],
            ['subject_type' => 'term', 'subject_id' => $term->id(), 'site' => 'default', 'field_path' => 'body', 'title' => 'News'],
            ['subject_type' => 'global', 'subject_id' => 'footer', 'site' => 'default', 'field_path' => 'body', 'title' => 'Footer'],
        ])->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.dead-links.index'))
            ->assertOk();

        $this->assertEquals([
            $entry->editUrl(),
            $term->inDefaultLocale()->editUrl(),
            $globalSet->in('default')->editUrl(),
        ], $response->json('data.0.references.*.edit_url'));
    }

    #[Test]
    public function references_to_custom_subjects_use_the_registered_edit_url_resolver()
    {
        ContentScanner::resolveEditUrlsUsing('product', fn (string $id, string $site) => "/cp/products/{$id}/{$site}");

        DeadLink::make()->id('abc')->url('https://example.com/broken')->references([
            ['subject_type' => 'product', 'subject_id' => '123', 'site' => 'default', 'field_path' => 'description', 'title' => 'Bobsleigh'],
        ])->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.dead-links.index'))
            ->assertOk();

        $this->assertEquals('/cp/products/123/default', $response->json('data.0.references.0.edit_url'));
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
    public function the_site_filter_is_offered_for_the_dead_links_listing_in_a_multisite()
    {
        $this->setSites();

        $filters = Scope::filters('dead-links');

        $this->assertTrue($filters->contains(fn ($filter) => $filter->handle() === 'seo_pro_site'));
    }

    #[Test]
    public function a_super_user_can_recheck_all_links()
    {
        Queue::fake();

        config()->set('statamic.seo-pro.dead_links.check.batch_size', 2);

        DeadLink::make()->id('one')->url('https://example.com/one')->save();
        DeadLink::make()->id('two')->url('https://example.com/two')->save();
        DeadLink::make()->id('three')->url('https://example.com/three')->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.dead-links.recheck-all'))
            ->assertOk();

        Queue::assertPushed(CheckDeadLinksJob::class, 2);
    }

    #[Test]
    #[DataProvider('recheckMessageProvider')]
    public function rechecking_all_links_sets_expectations_based_on_the_queue_connection(string $connection, string $message)
    {
        config()->set('queue.default', $connection);

        Queue::fake();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.dead-links.recheck-all'))
            ->assertOk()
            ->assertJson(['message' => __($message)]);
    }

    public static function recheckMessageProvider(): array
    {
        return [
            'sync' => ['sync', 'seo-pro::messages.dead_links_rechecked'],
            'queued' => ['redis', 'seo-pro::messages.dead_links_queued_for_rechecking'],
        ];
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

    private function setSites(): void
    {
        config()->set('statamic.editions.pro', true);
        config()->set('statamic.system.multisite', true);

        Site::setSites([
            'default' => ['url' => 'http://test.com', 'locale' => 'en_US'],
            'fr' => ['url' => 'http://test.fr', 'locale' => 'fr_FR'],
        ]);
    }
}
