<?php

namespace Tests\DeadLinks;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Create;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Stache;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\DeadLinks\LinkChecker;
use Statamic\SeoPro\Facades\DeadLink;
use Statamic\SeoPro\Notifications\DeadLinksDigest;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class LinkCheckerTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function it_checks_due_links_after_they_have_been_reloaded_from_disk()
    {
        Http::fake(['*' => Http::response(status: 404)]);

        DeadLink::make()
            ->id('abc')
            ->url('https://example.com/broken')
            ->nextCheckAt(now()->subHour())
            ->save();

        Stache::clear();

        $this->assertEquals(1, LinkChecker::checkDue());
        $this->assertEquals(Link::STATUS_FAILING, DeadLink::find('abc')->status());
    }

    #[Test]
    public function it_records_when_a_link_started_failing()
    {
        Http::fake(['*' => Http::response(status: 404)]);

        DeadLink::make()->id('abc')->url('https://example.com/broken')->save();

        $this->travelTo('2026-09-01 12:00:00');
        LinkChecker::checkLinks(collect([DeadLink::find('abc')]));

        $this->travelTo('2026-09-02 12:00:00');
        LinkChecker::checkLinks(collect([DeadLink::find('abc')]));

        $this->assertEquals('2026-09-01 12:00:00', DeadLink::find('abc')->failingSince()->toDateTimeString());
    }

    #[Test]
    public function it_clears_when_a_link_started_failing_once_it_recovers()
    {
        Http::fake(['*' => Http::response()]);

        DeadLink::make()
            ->id('abc')
            ->url('https://example.com/fixed')
            ->status(Link::STATUS_FAILING)
            ->failingSince(now()->subDay())
            ->save();

        LinkChecker::checkLinks(collect([DeadLink::find('abc')]));

        $this->assertEquals(Link::STATUS_OK, DeadLink::find('abc')->status());
        $this->assertNull(DeadLink::find('abc')->failingSince());
    }

    #[Test]
    public function it_describes_the_response_a_link_returned()
    {
        Http::fake(['*' => Http::response(status: 404)]);

        DeadLink::make()->id('abc')->url('https://example.com/broken')->save();

        LinkChecker::checkLinks(collect([DeadLink::find('abc')]));

        $this->assertEquals('404 Not Found', DeadLink::find('abc')->response());
    }

    #[Test]
    #[DataProvider('connectionErrorProvider')]
    public function it_describes_why_a_link_could_not_be_reached(string $error, string $response)
    {
        Http::fake(['*' => fn ($request) => Create::rejectionFor(new ConnectException($error, $request->toPsrRequest()))]);

        DeadLink::make()->id('abc')->url('https://example.com/broken')->save();

        LinkChecker::checkLinks(collect([DeadLink::find('abc')]));

        $this->assertEquals(Link::STATUS_FAILING, DeadLink::find('abc')->status());
        $this->assertEquals($response, DeadLink::find('abc')->response());
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

        config()->set('statamic.seo-pro.dead_links.notifications.recipients', ['duncan@example.com']);

        DeadLink::make()->id('abc')->url('https://example.com/broken')->status(Link::STATUS_FAILING)->save();

        LinkChecker::notifyIfNeeded();

        Notification::assertSentOnDemand(
            DeadLinksDigest::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === ['duncan@example.com']
        );
    }

    #[Test]
    public function it_does_not_send_a_digest_without_recipients()
    {
        Notification::fake();

        DeadLink::make()->id('abc')->url('https://example.com/broken')->status(Link::STATUS_FAILING)->save();

        LinkChecker::notifyIfNeeded();

        Notification::assertNothingSent();
    }
}
