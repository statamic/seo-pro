<?php

namespace Tests\Redirects;

use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Facades;
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
            ->sourceUrl('/old-url')
            ->destinationUrl('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->get('/old-url')
            ->assertRedirect('/new-url')
            ->assertStatus(301);
    }

    #[Test]
    public function it_redirects_with_302_response_code()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('/old-url')
            ->destinationUrl('/new-url')
            ->responseCode(302)
            ->enabled(true)
            ->save();

        $this->get('/old-url')
            ->assertRedirect('/new-url')
            ->assertStatus(302);
    }

    #[Test]
    public function it_does_not_redirect_to_inactive_redirect()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('/old-url')
            ->destinationUrl('/new-url')
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
            ->sourceUrl('/old-url')
            ->destinationUrl('/new-url')
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
            ->sourceUrl('/old-url')
            ->destinationUrl('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->get('/old-url?foo=bar&baz=qux')
            ->assertRedirect('/new-url?foo=bar&baz=qux');
    }

    #[Test]
    public function it_does_not_preserve_query_string_when_disabled()
    {
        config(['statamic.seo-pro.redirects.preserve_query_string' => false]);

        Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('/old-url')
            ->destinationUrl('/new-url')
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
            ->sourceUrl('/old-url')
            ->destinationUrl('/new-url?existing=param')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->get('/old-url?foo=bar')
            ->assertRedirect('/new-url?existing=param&foo=bar');
    }

    #[Test]
    public function it_redirects_to_absolute_urls()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('/old-url')
            ->destinationUrl('https://example.com/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->get('/old-url')
            ->assertRedirect('https://example.com/new-url');
    }

    #[Test]
    public function it_dispatches_a_job_to_record_the_hit()
    {
        Queue::fake();

        Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('/old-url')
            ->destinationUrl('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->get('/old-url')->assertRedirect('/new-url');

        Queue::assertPushed(RecordRedirectHit::class, function ($job) {
            return $job->redirectId === 'abc';
        });
    }

    #[Test]
    public function it_does_not_dispatch_a_job_when_no_redirect_matches()
    {
        Queue::fake();

        $this->get('/nonexistent-url')->assertNotFound();

        Queue::assertNotPushed(RecordRedirectHit::class);
    }
}
