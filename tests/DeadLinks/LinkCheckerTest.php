<?php

namespace Tests\DeadLinks;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Stache;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\DeadLinks\LinkChecker;
use Statamic\SeoPro\Facades\DeadLink;
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
}
