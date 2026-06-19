<?php

namespace Tests\Redirects;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\RecordError;
use Statamic\SeoPro\Redirects\RecordRedirectHit;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class HandleRedirectsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function it_redirects_matching_urls()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/old-url')
            ->assertRedirect('/new-url')
            ->assertStatus(301);
    }

    #[Test]
    public function it_redirects_with_302_response_code()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this
            ->get('/old-url')
            ->assertRedirect('/new-url')
            ->assertStatus(302);
    }

    #[Test]
    public function it_redirects_when_visiting_url_with_trailing_slash()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        // The test HTTP client strips trailing slashes before dispatching, so the
        // request is handled directly through the kernel to preserve it.
        $response = $this->app->handle(Request::create('/old-url/', 'GET'));

        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/new-url', parse_url($response->headers->get('Location'), PHP_URL_PATH));
    }

    #[Test]
    public function it_redirects_when_rule_has_trailing_slash_but_url_does_not()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url/')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/old-url')
            ->assertRedirect('/new-url')
            ->assertStatus(301);
    }

    #[Test]
    public function it_does_not_redirect_to_inactive_redirect()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(false)
            ->save();

        $this->get('/old-url')->assertNotFound();
    }

    #[Test]
    public function it_does_not_redirect_unmatched_urls()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->get('/some-other-url')->assertNotFound();
    }

    #[Test]
    public function it_preserves_query_string_when_enabled()
    {
        config(['statamic.seo-pro.redirects.preserve_query_string' => true]);

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $response = $this->get('/old-url?foo=bar&baz=qux');
        $response->assertStatus(301);

        $location = $response->headers->get('Location');
        $query = parse_url($location, PHP_URL_QUERY);
        parse_str($query, $params);

        $this->assertEquals('/new-url', parse_url($location, PHP_URL_PATH));
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $params);
    }

    #[Test]
    public function it_does_not_preserve_query_string_when_disabled()
    {
        config(['statamic.seo-pro.redirects.preserve_query_string' => false]);

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->get('/old-url?foo=bar')
            ->assertRedirect('/new-url');
    }

    #[Test]
    public function it_appends_query_string_when_destination_already_has_one()
    {
        config(['statamic.seo-pro.redirects.preserve_query_string' => true]);

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url?existing=param')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/old-url?foo=bar')
            ->assertRedirect('/new-url?existing=param&foo=bar');
    }

    #[Test]
    public function it_redirects_to_absolute_urls()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('https://example.com/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/old-url')
            ->assertRedirect('https://example.com/new-url');
    }

    #[Test]
    public function it_dispatches_a_job_to_record_the_hit()
    {
        Queue::fake();

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->get('/old-url')->assertRedirect('/new-url');

        Queue::assertPushed(RecordRedirectHit::class, fn ($job) => $job->redirectId === 'abc');
    }

    #[Test]
    public function it_does_not_dispatch_a_redirect_hit_job_when_no_redirect_matches()
    {
        Queue::fake();

        $this->get('/nonexistent-url')->assertNotFound();

        Queue::assertNotPushed(RecordRedirectHit::class);
    }

    #[Test]
    public function it_does_not_dispatch_a_redirect_hit_job_when_tracking_is_disabled()
    {
        Queue::fake();

        config(['statamic.seo-pro.redirects.track_hits' => false]);

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->get('/old-url')->assertRedirect('/new-url');

        Queue::assertNotPushed(RecordRedirectHit::class);
    }

    #[Test]
    public function it_dispatches_a_record_error_job_when_no_redirect_matches()
    {
        Queue::fake();

        config(['statamic.seo-pro.redirects.errors.enabled' => true]);

        $this->get('/nonexistent-url')->assertNotFound();

        Queue::assertPushed(RecordError::class, fn ($job) => $job->url === '/nonexistent-url' && $job->site === 'default');
    }

    #[Test]
    public function it_does_not_dispatch_a_record_error_job_when_errors_are_disabled()
    {
        Queue::fake();

        config(['statamic.seo-pro.redirects.errors.enabled' => false]);

        $this->get('/nonexistent-url')->assertNotFound();

        Queue::assertNotPushed(RecordError::class);
    }

    #[Test]
    public function it_does_not_dispatch_a_record_error_job_when_redirect_matches()
    {
        Queue::fake();

        config(['statamic.seo-pro.redirects.errors.enabled' => true]);

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/old-url')
            ->assertRedirect('/new-url');

        Queue::assertNotPushed(RecordError::class);
    }

    #[Test]
    public function it_redirects_matching_wildcard_urls()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/blog/*')
            ->destination('/articles/$1')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/blog/hello-world')
            ->assertRedirect('/articles/hello-world')
            ->assertStatus(301);
    }

    #[Test]
    public function it_redirects_with_multiple_wildcards()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/blog/*/posts/*')
            ->destination('/articles/$1/entries/$2')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/blog/2026/posts/hello-world')
            ->assertRedirect('/articles/2026/entries/hello-world');
    }

    #[Test]
    public function it_prefers_exact_match_over_wildcard()
    {
        Facades\Redirect::make()
            ->id('wildcard')
            ->source('/blog/*')
            ->destination('/articles/$1')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        Facades\Redirect::make()
            ->id('exact')
            ->source('/blog/specific-post')
            ->destination('/exact-destination')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/blog/specific-post')
            ->assertRedirect('/exact-destination');
    }

    #[Test]
    public function it_does_not_redirect_inactive_wildcard_redirects()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/blog/*')
            ->destination('/articles/$1')
            ->responseCode(301)
            ->enabled(false)
            ->save();

        $this
            ->get('/blog/hello-world')
            ->assertNotFound();
    }

    #[Test]
    public function it_preserves_query_string_on_wildcard_redirects()
    {
        config(['statamic.seo-pro.redirects.preserve_query_string' => true]);

        Facades\Redirect::make()
            ->id('abc')
            ->source('/blog/*')
            ->destination('/articles/$1')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/blog/hello-world?ref=twitter')
            ->assertRedirect('/articles/hello-world?ref=twitter');
    }

    #[Test]
    public function it_dispatches_hit_job_for_wildcard_redirects()
    {
        Queue::fake();

        Facades\Redirect::make()
            ->id('abc')
            ->source('/blog/*')
            ->destination('/articles/$1')

            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/blog/hello-world')
            ->assertRedirect('/articles/hello-world');

        Queue::assertPushed(RecordRedirectHit::class, fn ($job) => $job->redirectId === 'abc');
    }

    #[Test]
    public function it_does_not_match_wildcard_when_pattern_does_not_match()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/blog/*')
            ->destination('/articles/$1')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/articles/hello-world')
            ->assertNotFound();
    }

    #[Test]
    public function it_redirects_to_an_entry()
    {
        $collection = tap(Collection::make('posts')->routes('/posts/{slug}'))->save();

        $entry = tap(Entry::make()
            ->id('post-1')
            ->collection($collection)
            ->slug('hello-world')
            ->data(['title' => 'Hello World'])
        )->save();

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-post')
            ->destination('entry::post-1')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this
            ->get('/old-post')
            ->assertRedirect($entry->absoluteUrl())
            ->assertStatus(301);
    }
}
