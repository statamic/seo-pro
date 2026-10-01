<?php

namespace Tests\BrokenLinks;

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
use Statamic\SeoPro\BrokenLinks\EditUrls;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Jobs\CheckExternalLinksJob;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ViewBrokenLinksTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function can_view_broken_links_index()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.broken-links.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('seo-pro::BrokenLinks/Index'));
    }

    #[Test]
    public function the_seo_pro_tools_page_links_to_broken_links_when_permitted()
    {
        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->get(cp_route('seo-pro.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canViewBrokenLinks', true));
    }

    #[Test]
    public function the_seo_pro_tools_page_hides_broken_links_without_permission()
    {
        Role::make('test')->addPermission('access cp')->addPermission('view seo reports')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->get(cp_route('seo-pro.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canViewBrokenLinks', false));
    }

    #[Test]
    public function cant_view_broken_links_without_permission()
    {
        Role::make('test')->addPermission('access cp')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->get(cp_route('seo-pro.broken-links.index'))
            ->assertRedirect('/cp');
    }

    #[Test]
    public function can_view_broken_links_as_json()
    {
        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->status('failing')->save();
        Facades\ExternalLink::make()->id('def')->url('https://example.com/fine')->status('ok')->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.broken-links.index'))
            ->assertOk();

        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function only_broken_links_in_authorized_sites_are_listed()
    {
        $this->setSites();

        Facades\ExternalLink::make()->id('english')->site('default')->url('https://example.com/english')->save();
        Facades\ExternalLink::make()->id('french')->site('fr')->url('https://example.com/french')->save();

        Role::make('test')
            ->addPermission('access cp')
            ->addPermission('view seo broken links')
            ->addPermission('access fr site')
            ->save();

        $response = $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->getJson(cp_route('seo-pro.broken-links.index'))
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

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->references([
            ['subject_type' => 'entry', 'subject_id' => $entry->id(), 'site' => 'default', 'field_path' => 'body', 'title' => 'Hello World'],
            ['subject_type' => 'term', 'subject_id' => $term->id(), 'site' => 'default', 'field_path' => 'body', 'title' => 'News'],
            ['subject_type' => 'global', 'subject_id' => 'footer', 'site' => 'default', 'field_path' => 'body', 'title' => 'Footer'],
        ])->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.broken-links.index'))
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
        EditUrls::resolveUsing('product', fn (string $id, string $site) => "/cp/products/{$id}/{$site}");

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->references([
            ['subject_type' => 'product', 'subject_id' => '123', 'site' => 'default', 'field_path' => 'description', 'title' => 'Bobsleigh'],
        ])->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.broken-links.index'))
            ->assertOk();

        $this->assertEquals('/cp/products/123/default', $response->json('data.0.references.0.edit_url'));
    }

    #[Test]
    public function broken_links_are_sorted_with_the_longest_failing_first_by_default()
    {
        Facades\ExternalLink::make()->id('ok')->url('https://example.com/ok')->status('ok')->save();
        Facades\ExternalLink::make()->id('recent')->url('https://example.com/recent')->status('failing')->failingSince(now()->subDay())->save();
        Facades\ExternalLink::make()->id('oldest')->url('https://example.com/oldest')->status('failing')->failingSince(now()->subWeek())->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.broken-links.index'))
            ->assertOk();

        $this->assertEquals(['oldest', 'recent', 'ok'], collect($response->json('data'))->pluck('id')->all());
    }

    #[Test]
    public function failing_links_say_how_long_they_have_been_failing()
    {
        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->status('failing')->failingSince(now()->subWeeks(3))->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.broken-links.index'))
            ->assertOk();

        $this->assertEquals('Failing for 3 weeks', $response->json('data.0.status_label'));
    }

    #[Test]
    public function the_listing_describes_the_response_each_link_returned()
    {
        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->status('failing')->statusCode(404)->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.broken-links.index'))
            ->assertOk();

        $this->assertEquals('404 Not Found', $response->json('data.0.response'));
    }

    #[Test]
    public function broken_links_can_be_searched_by_url()
    {
        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->save();
        Facades\ExternalLink::make()->id('def')->url('https://example.com/fine')->save();

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.broken-links.index', ['search' => 'broken']))
            ->assertOk();

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('abc', $data[0]['id']);
    }

    #[Test]
    public function broken_links_can_be_filtered_by_status()
    {
        Facades\ExternalLink::make()->id('broken')->url('https://example.com/broken')->status('failing')->save();
        Facades\ExternalLink::make()->id('fine')->url('https://example.com/fine')->status('ok')->save();

        // The real Listing component sends `filters` as a single base64-encoded
        // JSON string (see Statamic\Http\Requests\FilteredRequest), not nested
        // query params.
        $filters = base64_encode(json_encode(['broken_link_status' => ['status' => 'failing']]));

        $response = $this
            ->actingAs(User::make()->makeSuper()->save())
            ->getJson(cp_route('seo-pro.broken-links.index', ['filters' => $filters]))
            ->assertOk();

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('broken', $data[0]['id']);
        $this->assertEquals(['broken_link_status' => 'Failing'], $response->json('meta.activeFilterBadges'));
    }

    #[Test]
    public function the_status_filter_is_offered_for_the_broken_links_listing()
    {
        $filters = Scope::filters('broken-links');

        $this->assertTrue($filters->contains(fn ($filter) => $filter->handle() === 'broken_link_status'));
    }

    #[Test]
    public function the_site_filter_is_offered_for_the_broken_links_listing_in_a_multisite()
    {
        $this->setSites();

        $filters = Scope::filters('broken-links');

        $this->assertTrue($filters->contains(fn ($filter) => $filter->handle() === 'seo_pro_site'));
    }

    #[Test]
    public function a_super_user_can_recheck_all_links()
    {
        Queue::fake();

        config()->set('statamic.seo-pro.broken_links.check.batch_size', 2);

        Facades\ExternalLink::make()->id('one')->url('https://example.com/one')->save();
        Facades\ExternalLink::make()->id('two')->url('https://example.com/two')->save();
        Facades\ExternalLink::make()->id('three')->url('https://example.com/three')->save();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.broken-links.recheck-all'))
            ->assertOk();

        Queue::assertPushed(CheckExternalLinksJob::class, 2);
    }

    #[Test]
    #[DataProvider('recheckMessageProvider')]
    public function rechecking_all_links_sets_expectations_based_on_the_queue_connection(string $connection, string $message)
    {
        config()->set('queue.default', $connection);

        Queue::fake();

        $this
            ->actingAs(User::make()->makeSuper()->save())
            ->postJson(cp_route('seo-pro.broken-links.recheck-all'))
            ->assertOk()
            ->assertJson(['message' => __($message)]);
    }

    public static function recheckMessageProvider(): array
    {
        return [
            'sync' => ['sync', 'seo-pro::messages.broken_links_rechecked'],
            'queued' => ['redis', 'seo-pro::messages.broken_links_queued_for_rechecking'],
        ];
    }

    #[Test]
    public function a_user_who_can_view_broken_links_can_recheck_all_links()
    {
        Role::make('test')->addPermission('access cp')->addPermission('view seo broken links')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->postJson(cp_route('seo-pro.broken-links.recheck-all'))
            ->assertOk();
    }

    #[Test]
    public function a_user_who_cannot_view_broken_links_cannot_recheck_all_links()
    {
        Role::make('test')->addPermission('access cp')->save();

        $this
            ->actingAs(User::make()->assignRole('test')->save())
            ->postJson(cp_route('seo-pro.broken-links.recheck-all'))
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
