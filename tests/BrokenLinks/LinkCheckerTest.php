<?php

namespace Tests\BrokenLinks;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\Create;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\BrokenLinks\LinkChecker;
use Statamic\SeoPro\BrokenLinks\Reference;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class LinkCheckerTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function it_does_not_bring_back_a_link_that_was_deleted_while_being_checked()
    {
        $link = tap(Facades\ExternalLink::make()->id('abc')->url('https://example.com/removed'))->save();

        Http::fake(['*' => function () use ($link) {
            $link->delete();

            return Http::response();
        }]);

        app(LinkChecker::class)->check(collect([clone $link]));

        $this->assertNull(Facades\ExternalLink::find('abc'));
    }

    #[Test]
    public function it_keeps_references_added_while_a_link_was_being_checked()
    {
        $link = tap(Facades\ExternalLink::make()->id('abc')->url('https://example.com/page'))->save();

        Http::fake(['*' => function () {
            Facades\ExternalLink::find('abc')->references([
                new Reference(type: 'entry', id: '1', site: 'default'),
            ])->save();

            return Http::response();
        }]);

        app(LinkChecker::class)->check(collect([clone $link]));

        $this->assertNull(Facades\ExternalLink::find('abc')->brokenSince());
        $this->assertCount(1, Facades\ExternalLink::find('abc')->references());
    }

    #[Test]
    public function it_records_when_a_link_broke()
    {
        Http::fake(['*' => Http::response(status: 404)]);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->save();

        $this->travelTo('2026-09-01 12:00:00');
        app(LinkChecker::class)->check(collect([Facades\ExternalLink::find('abc')]));

        $this->travelTo('2026-09-02 12:00:00');
        app(LinkChecker::class)->check(collect([Facades\ExternalLink::find('abc')]));

        $this->assertEquals('2026-09-01 12:00:00', Facades\ExternalLink::find('abc')->brokenSince()->toDateTimeString());
    }

    #[Test]
    public function it_clears_when_a_link_broke_once_it_recovers()
    {
        Http::fake(['*' => Http::response()]);

        Facades\ExternalLink::make()
            ->id('abc')
            ->url('https://example.com/fixed')
            ->brokenSince(now()->subDay())
            ->save();

        app(LinkChecker::class)->check(collect([Facades\ExternalLink::find('abc')]));

        $this->assertNull(Facades\ExternalLink::find('abc')->brokenSince());
    }

    #[Test]
    public function it_describes_the_response_a_link_returned()
    {
        Http::fake(['*' => Http::response(status: 404)]);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->save();

        app(LinkChecker::class)->check(collect([Facades\ExternalLink::find('abc')]));

        $this->assertEquals('404 Not Found', Facades\ExternalLink::find('abc')->response());
    }

    #[Test]
    #[DataProvider('connectionErrorProvider')]
    public function it_describes_why_a_link_could_not_be_reached(string $error, string $response)
    {
        Http::fake(['*' => fn ($request) => Create::rejectionFor(new ConnectException($error, $request->toPsrRequest()))]);

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/broken')->save();

        app(LinkChecker::class)->check(collect([Facades\ExternalLink::find('abc')]));

        $this->assertNotNull(Facades\ExternalLink::find('abc')->brokenSince());
        $this->assertEquals($response, Facades\ExternalLink::find('abc')->response());
    }

    #[Test]
    public function it_retries_with_a_get_request_when_head_requests_are_not_allowed()
    {
        Http::fake(fn ($request) => $request->method() === 'HEAD' ? Http::response(status: 405) : Http::response());

        Facades\ExternalLink::make()->id('abc')->url('https://example.com/page')->save();

        app(LinkChecker::class)->check(collect([Facades\ExternalLink::find('abc')]));

        Http::assertSentCount(2);
        $this->assertNull(Facades\ExternalLink::find('abc')->brokenSince());
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

        app(LinkChecker::class)->check(collect([Facades\ExternalLink::find('abc')]));

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
}
