<?php

namespace Tests\BrokenLinks;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Stache;
use Statamic\SeoPro\BrokenLinks\Reference;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Notifications\BrokenLinksDigest;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class CheckBrokenLinksCommandTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.seo-pro.broken_links.enabled', true);
    }

    #[Test]
    public function it_checks_due_links_after_they_have_been_reloaded_from_disk()
    {
        Http::fake(['*' => Http::response(status: 404)]);

        Facades\ExternalLink::make()
            ->id('abc')
            ->url('https://example.com/broken')
            ->nextCheckAt(now()->subHour())
            ->save();

        Stache::clear();

        $this->artisan('statamic:seo-pro:check-broken-links')->expectsOutputToContain('Checked 1 link(s).');
        $this->assertNotNull(Facades\ExternalLink::find('abc')->brokenSince());
    }

    #[Test]
    public function it_checks_the_most_overdue_links_first_up_to_the_batch_size()
    {
        Http::fake(['*' => Http::response()]);

        config()->set('statamic.seo-pro.broken_links.check.batch_size', 2);

        Facades\ExternalLink::make()->id('recent')->url('https://example.com/recent')->nextCheckAt(now()->subMinute())->save();
        Facades\ExternalLink::make()->id('oldest')->url('https://example.com/oldest')->nextCheckAt(now()->subDay())->save();
        Facades\ExternalLink::make()->id('older')->url('https://example.com/older')->nextCheckAt(now()->subHour())->save();
        Facades\ExternalLink::make()->id('later')->url('https://example.com/later')->nextCheckAt(now()->addHour())->save();

        $this->artisan('statamic:seo-pro:check-broken-links')->expectsOutputToContain('Checked 2 link(s).');

        $this->assertNotNull(Facades\ExternalLink::find('oldest')->checkedAt());
        $this->assertNotNull(Facades\ExternalLink::find('older')->checkedAt());
        $this->assertNull(Facades\ExternalLink::find('recent')->checkedAt());
        $this->assertNull(Facades\ExternalLink::find('later')->checkedAt());
    }

    #[Test]
    public function it_does_not_check_links_when_broken_link_checking_is_disabled()
    {
        Http::fake();

        config()->set('statamic.seo-pro.broken_links.enabled', false);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/page')->nextCheckAt(now()->subHour())->save();

        $this->artisan('statamic:seo-pro:check-broken-links')
            ->expectsOutputToContain('Broken link checking is disabled.')
            ->assertFailed();

        Http::assertNothingSent();
    }

    #[Test]
    public function it_sends_a_digest_of_broken_links_to_recipients()
    {
        Notification::fake();

        config()->set('statamic.seo-pro.broken_links.notifications.recipients', ['duncan@example.com']);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->brokenSince(now()->subDays(2))->save();

        $this->artisan('statamic:seo-pro:check-broken-links');

        Notification::assertSentOnDemand(
            BrokenLinksDigest::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === ['duncan@example.com']
        );
    }

    #[Test]
    public function it_waits_a_day_before_notifying_about_a_broken_link()
    {
        Notification::fake();

        config()->set('statamic.seo-pro.broken_links.notifications.recipients', ['duncan@example.com']);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/flaky')->brokenSince(now()->subHour())->save();

        $this->artisan('statamic:seo-pro:check-broken-links');

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_only_notifies_about_a_broken_link_once()
    {
        Notification::fake();

        config()->set('statamic.seo-pro.broken_links.notifications.recipients', ['duncan@example.com']);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->brokenSince(now()->subDays(2))->save();

        $this->artisan('statamic:seo-pro:check-broken-links');
        $this->artisan('statamic:seo-pro:check-broken-links');

        Notification::assertSentOnDemandTimes(BrokenLinksDigest::class, 1);
    }

    #[Test]
    public function the_digest_lists_each_broken_link_and_where_it_was_found()
    {
        Collection::make('pages')->save();

        $home = tap(Entry::make()->collection('pages')->slug('home')->data(['title' => 'Home']))->save();
        $about = tap(Entry::make()->collection('pages')->slug('about')->data(['title' => 'About']))->save();

        $link = Facades\ExternalLink::make()
            ->url('https://example.com/broken')
            ->statusCode(404)
            ->references([
                new Reference(type: 'entry', id: $home->id(), site: 'default'),
                new Reference(type: 'entry', id: $about->id(), site: 'default'),
            ]);

        $mail = (new BrokenLinksDigest(collect([$link])))->toMail(null);

        $this->assertEquals('1 broken link found', $mail->subject);
        $this->assertEquals([
            '1 external link is currently broken.',
            'https://example.com/broken — 404 Not Found',
            'Found in: Home, About',
        ], $mail->introLines);
        $this->assertEquals('View Broken Links', $mail->actionText);
        $this->assertEquals(cp_route('seo-pro.broken-links.index'), $mail->actionUrl);
    }

    #[Test]
    public function it_does_not_send_a_digest_without_recipients()
    {
        Notification::fake();

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->brokenSince(now()->subDays(2))->save();

        $this->artisan('statamic:seo-pro:check-broken-links');

        Notification::assertNothingSent();
    }
}
