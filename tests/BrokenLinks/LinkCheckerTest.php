<?php

namespace Tests\BrokenLinks;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Create;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Stache;
use Statamic\SeoPro\BrokenLinks\LinkChecker;
use Statamic\SeoPro\BrokenLinks\LinkStatus;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Notifications\BrokenLinksDigest;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class LinkCheckerTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

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

        $this->assertEquals(1, LinkChecker::checkDue());
        $this->assertEquals(LinkStatus::Broken, Facades\ExternalLink::find('abc')->status());
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

        $this->assertEquals(2, LinkChecker::checkDue());

        $this->assertEquals(LinkStatus::Ok, Facades\ExternalLink::find('oldest')->status());
        $this->assertEquals(LinkStatus::Ok, Facades\ExternalLink::find('older')->status());
        $this->assertEquals(LinkStatus::Pending, Facades\ExternalLink::find('recent')->status());
        $this->assertEquals(LinkStatus::Pending, Facades\ExternalLink::find('later')->status());
    }

    #[Test]
    public function it_does_not_bring_back_a_link_that_was_deleted_while_being_checked()
    {
        $link = tap(Facades\ExternalLink::make()->id('abc')->url('https://example.com/removed'))->save();

        Http::fake(['*' => function () use ($link) {
            $link->delete();

            return Http::response();
        }]);

        LinkChecker::checkLinks(collect([clone $link]));

        $this->assertNull(Facades\ExternalLink::find('abc'));
    }

    #[Test]
    public function it_keeps_references_added_while_a_link_was_being_checked()
    {
        $link = tap(Facades\ExternalLink::make()->id('abc')->url('https://example.com/page'))->save();

        Http::fake(['*' => function () {
            Facades\ExternalLink::find('abc')->references([
                ['subject_type' => 'entry', 'subject_id' => '1', 'site' => 'default', 'field_path' => 'body', 'title' => 'Home'],
            ])->save();

            return Http::response();
        }]);

        LinkChecker::checkLinks(collect([clone $link]));

        $this->assertEquals(LinkStatus::Ok, Facades\ExternalLink::find('abc')->status());
        $this->assertCount(1, Facades\ExternalLink::find('abc')->references());
    }

    #[Test]
    public function it_records_when_a_link_started_failing()
    {
        Http::fake(['*' => Http::response(status: 404)]);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->save();

        $this->travelTo('2026-09-01 12:00:00');
        LinkChecker::checkLinks(collect([Facades\ExternalLink::find('abc')]));

        $this->travelTo('2026-09-02 12:00:00');
        LinkChecker::checkLinks(collect([Facades\ExternalLink::find('abc')]));

        $this->assertEquals('2026-09-01 12:00:00', Facades\ExternalLink::find('abc')->failingSince()->toDateTimeString());
    }

    #[Test]
    public function it_clears_when_a_link_started_failing_once_it_recovers()
    {
        Http::fake(['*' => Http::response()]);

        Facades\ExternalLink::make()
            ->id('abc')
            ->url('https://example.com/fixed')
            ->status(LinkStatus::Broken)
            ->failingSince(now()->subDay())
            ->save();

        LinkChecker::checkLinks(collect([Facades\ExternalLink::find('abc')]));

        $this->assertEquals(LinkStatus::Ok, Facades\ExternalLink::find('abc')->status());
        $this->assertNull(Facades\ExternalLink::find('abc')->failingSince());
    }

    #[Test]
    public function it_describes_the_response_a_link_returned()
    {
        Http::fake(['*' => Http::response(status: 404)]);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->save();

        LinkChecker::checkLinks(collect([Facades\ExternalLink::find('abc')]));

        $this->assertEquals('404 Not Found', Facades\ExternalLink::find('abc')->response());
    }

    #[Test]
    #[DataProvider('connectionErrorProvider')]
    public function it_describes_why_a_link_could_not_be_reached(string $error, string $response)
    {
        Http::fake(['*' => fn ($request) => Create::rejectionFor(new ConnectException($error, $request->toPsrRequest()))]);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->save();

        LinkChecker::checkLinks(collect([Facades\ExternalLink::find('abc')]));

        $this->assertEquals(LinkStatus::Broken, Facades\ExternalLink::find('abc')->status());
        $this->assertEquals($response, Facades\ExternalLink::find('abc')->response());
    }

    #[Test]
    public function it_retries_with_a_get_request_when_head_requests_are_not_allowed()
    {
        Http::fake(fn ($request) => $request->method() === 'HEAD' ? Http::response(status: 405) : Http::response());

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/page')->save();

        LinkChecker::checkLinks(collect([Facades\ExternalLink::find('abc')]));

        Http::assertSentCount(2);
        $this->assertEquals(LinkStatus::Ok, Facades\ExternalLink::find('abc')->status());
    }

    #[Test]
    public function it_does_not_retry_links_that_could_not_be_reached()
    {
        $attempts = 0;

        Http::fake(['*' => function ($request) use (&$attempts) {
            $attempts++;

            return Create::rejectionFor(new ConnectException('cURL error 6: Could not resolve host', $request->toPsrRequest()));
        }]);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->save();

        LinkChecker::checkLinks(collect([Facades\ExternalLink::find('abc')]));

        $this->assertEquals(1, $attempts);
    }

    public static function connectionErrorProvider(): array
    {
        return [
            'host not found' => ['cURL error 6: Could not resolve host: example.com', 'Host not found'],
            'connection refused' => ['cURL error 7: Failed to connect to example.com port 443', 'Connection refused'],
            'timed out' => ['cURL error 28: Operation timed out after 10001 milliseconds', 'Timed out'],
            'ssl error' => ['cURL error 60: SSL certificate problem: certificate has expired', 'SSL error'],
            'anything else' => ['cURL error 56: Recv failure: Connection reset by peer', "Couldn't connect"],
        ];
    }

    #[Test]
    public function it_sends_a_digest_of_failing_links_to_recipients()
    {
        Notification::fake();

        config()->set('statamic.seo-pro.broken_links.notifications.recipients', ['duncan@example.com']);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->status(LinkStatus::Broken)->failingSince(now()->subDays(2))->save();

        LinkChecker::notifyIfNeeded();

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

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/flaky')->status(LinkStatus::Broken)->failingSince(now()->subHour())->save();

        LinkChecker::notifyIfNeeded();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_only_notifies_about_a_broken_link_once()
    {
        Notification::fake();

        config()->set('statamic.seo-pro.broken_links.notifications.recipients', ['duncan@example.com']);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->status(LinkStatus::Broken)->failingSince(now()->subDays(2))->save();

        LinkChecker::notifyIfNeeded();
        LinkChecker::notifyIfNeeded();

        Notification::assertSentOnDemandTimes(BrokenLinksDigest::class, 1);
    }

    #[Test]
    public function the_digest_lists_each_broken_link_and_where_it_was_found()
    {
        $link = Facades\ExternalLink::make()
            ->url('https://example.com/broken')
            ->statusCode(404)
            ->references([
                ['subject_type' => 'entry', 'subject_id' => '1', 'site' => 'default', 'field_path' => 'body', 'title' => 'Home'],
                ['subject_type' => 'entry', 'subject_id' => '1', 'site' => 'default', 'field_path' => 'sidebar', 'title' => 'Home'],
            ]);

        $mail = (new BrokenLinksDigest(collect([$link])))->toMail(null);

        $this->assertEquals('1 broken link found', $mail->subject);
        $this->assertEquals([
            '1 external link is currently broken.',
            'https://example.com/broken — 404 Not Found',
            'Found in: Home',
        ], $mail->introLines);
    }

    #[Test]
    public function it_does_not_send_a_digest_without_recipients()
    {
        Notification::fake();

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->status(LinkStatus::Broken)->failingSince(now()->subDays(2))->save();

        LinkChecker::notifyIfNeeded();

        Notification::assertNothingSent();
    }
}
