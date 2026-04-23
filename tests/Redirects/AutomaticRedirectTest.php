<?php

namespace Tests\Redirects;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\SeoPro\Facades\Redirect;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class AutomaticRedirectTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.seo-pro.redirects.directory', base_path('content/seo-pro/redirects'));

        $app['config']->set('statamic.seo-pro.redirects.automatic_redirects', [
            'enabled' => true,
            'collections' => ['*'],
            'taxonomies' => ['*'],
        ]);
    }

    #[Test]
    public function it_creates_a_redirect_when_entry_slug_changes()
    {
        Collection::make('posts')->routes('/posts/{slug}')->save();
        $entry = tap(Entry::make()->id('test-entry')->collection('posts')->slug('old-slug')->data(['title' => 'Test']))->save();

        $entry->slug('new-slug')->save();

        $redirect = Redirect::query()->where('source', '/posts/old-slug')->first();

        $this->assertNotNull($redirect);
        $this->assertEquals('entry::test-entry', $redirect->destination());
        $this->assertEquals(301, $redirect->responseCode());
        $this->assertTrue($redirect->enabled());
    }

    #[Test]
    public function it_does_not_create_a_redirect_for_new_entries()
    {
        Collection::make('posts')->routes('/posts/{slug}')->save();

        tap(Entry::make()->id('new-entry')->collection('posts')->slug('my-slug')->data(['title' => 'Test']))->save();

        $this->assertNull(Redirect::query()->first());
    }

    #[Test]
    public function it_does_not_create_a_redirect_for_entries_when_slug_has_not_changed()
    {
        Collection::make('posts')->routes('/posts/{slug}')->save();
        $entry = tap(Entry::make()->id('test-entry')->collection('posts')->slug('my-slug')->data(['title' => 'Test']))->save();

        $entry->set('title', 'Updated Title')->save();

        $this->assertNull(Redirect::query()->first());
    }

    #[Test]
    public function it_respects_collection_filtering()
    {
        config(['statamic.seo-pro.redirects.automatic_redirects.collections' => ['pages']]);

        // Redirect is created for the Pages collection...
        Collection::make('pages')->routes('/{slug}')->save();
        $entry = tap(Entry::make()->id('home')->collection('pages')->slug('old-slug')->data(['title' => 'Test']))->save();
        $entry->slug('new-slug')->save();
        $this->assertNotNull(Redirect::query()->where('source', '/old-slug')->first());

        // But not for the Posts collection...
        Collection::make('posts')->routes('/posts/{slug}')->save();
        $entry = tap(Entry::make()->id('test-entry')->collection('posts')->slug('old-slug')->data(['title' => 'Test']))->save();
        $entry->slug('new-slug')->save();
        $this->assertNull(Redirect::query()->where('source', '/posts/old-slug')->first());
    }

    #[Test]
    public function it_uses_configured_default_response_code()
    {
        config(['statamic.seo-pro.redirects.default_response_code' => 302]);

        Collection::make('posts')->routes('/posts/{slug}')->save();
        $entry = tap(Entry::make()->id('test-entry')->collection('posts')->slug('old-slug')->data(['title' => 'Test']))->save();

        $entry->slug('new-slug')->save();

        $redirect = Redirect::query()->where('source', '/posts/old-slug')->first();

        $this->assertNotNull($redirect);
        $this->assertEquals(302, $redirect->responseCode());
    }

    #[Test]
    public function it_updates_existing_entry_redirect_with_same_source()
    {
        Collection::make('posts')->routes('/posts/{slug}')->save();

        Redirect::make()
            ->id('existing')
            ->source('/posts/old-slug')
            ->destination('/somewhere-else')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $entry = tap(Entry::make()->id('test-entry')->collection('posts')->slug('old-slug')->data(['title' => 'Test']))->save();

        $entry->slug('new-slug')->save();

        $this->assertCount(1, Redirect::query()->where('source', '/posts/old-slug')->get());
        $this->assertEquals('entry::test-entry', Redirect::find('existing')->destination());
    }

    #[Test]
    public function it_cleans_up_entry_redirects_that_would_cause_infinite_loops()
    {
        Collection::make('posts')->routes('/posts/{slug}')->save();
        $entry = tap(Entry::make()->id('test-entry')->collection('posts')->slug('slug-a')->data(['title' => 'Test']))->save();

        $entry->slug('slug-b')->save();
        $entry->slug('slug-a')->save();

        // The redirect from /posts/slug-a should be deleted since the entry is back at /posts/slug-a.
        $this->assertNull(Redirect::query()->where('source', '/posts/slug-a')->first());

        // The redirect from /posts/slug-b should still exist.
        $redirectB = Redirect::query()->where('source', '/posts/slug-b')->first();
        $this->assertNotNull($redirectB);
        $this->assertEquals('entry::test-entry', $redirectB->destination());
    }

    #[Test]
    public function it_does_not_create_a_redirect_for_entries_without_routes()
    {
        Collection::make('posts')->save();

        $entry = tap(Entry::make()->id('test-entry')->collection('posts')->slug('old-slug')->data(['title' => 'Test']))->save();

        $entry->slug('new-slug')->save();

        $this->assertNull(Redirect::query()->first());
    }

    #[Test]
    public function it_does_not_create_entry_redirects_when_automatic_redirects_are_disabled()
    {
        config(['statamic.seo-pro.redirects.automatic_redirects.enabled' => false]);

        Collection::make('posts')->routes('/posts/{slug}')->save();
        $entry = tap(Entry::make()->id('test-entry')->collection('posts')->slug('old-slug')->data(['title' => 'Test']))->save();

        $entry->slug('new-slug')->save();

        $this->assertNull(Redirect::query()->where('source', '/posts/old-slug')->first());
    }

    #[Test]
    public function it_creates_a_redirect_when_term_slug_changes()
    {
        Taxonomy::make('tags')->save();
        $term = tap(Term::make()->taxonomy('tags')->slug('old-slug')->data(['title' => 'Test']))->save();

        $term->slug('new-slug')->save();

        $redirect = Redirect::query()->where('source', '/tags/old-slug')->first();

        $this->assertNotNull($redirect);
        $this->assertEquals('/tags/new-slug', $redirect->destination());
        $this->assertEquals(301, $redirect->responseCode());
        $this->assertTrue($redirect->enabled());
    }

    #[Test]
    public function it_does_not_create_a_redirect_for_new_terms()
    {
        Taxonomy::make('tags')->save();

        tap(Term::make()->taxonomy('tags')->slug('my-slug')->data(['title' => 'Test']))->save();

        $this->assertNull(Redirect::query()->first());
    }

    #[Test]
    public function it_does_not_create_a_redirect_for_terms_when_slug_has_not_changed()
    {
        Taxonomy::make('tags')->save();
        $term = tap(Term::make()->taxonomy('tags')->slug('old-slug')->data(['title' => 'Test']))->save();

        $term->set('title', 'Updated Title')->save();

        $redirect = Redirect::query()->where('source', '/tags/old-slug')->first();

        $this->assertNull(Redirect::query()->first());
    }

    #[Test]
    public function it_respects_taxonomy_filtering()
    {
        config(['statamic.seo-pro.redirects.automatic_redirects.taxonomies' => ['categories']]);

        // Redirect is created for the Categories taxonomy...
        Taxonomy::make('categories')->save();
        $term = tap(Term::make()->taxonomy('categories')->slug('old-category')->data(['title' => 'Test']))->save();
        $term->slug('new-category')->save();
        $this->assertNotNull(Redirect::query()->where('source', '/categories/old-category')->first());

        // But not for the Tags taxonomy...
        Taxonomy::make('tags')->save();
        $term = tap(Term::make()->taxonomy('tags')->slug('old-tag')->data(['title' => 'Test']))->save();
        $term->slug('new-tag')->save();
        $this->assertNull(Redirect::query()->where('source', '/tags/old-tag')->first());
    }

    #[Test]
    public function it_does_not_create_term_redirects_when_automatic_redirects_are_disabled()
    {
        config(['statamic.seo-pro.redirects.automatic_redirects.enabled' => false]);

        Taxonomy::make('tags')->save();
        $term = tap(Term::make()->taxonomy('tags')->slug('old-slug')->data(['title' => 'Test']))->save();

        $term->slug('new-slug')->save();

        $this->assertNull(Redirect::query()->where('source', '/tags/old-slug')->first());
    }
}
