<?php

namespace Tests\DeadLinks;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
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
