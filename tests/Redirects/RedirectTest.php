<?php

namespace Tests\Redirects;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Events\RedirectCreated;
use Statamic\SeoPro\Events\RedirectDeleted;
use Statamic\SeoPro\Events\RedirectSaved;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function redirect_can_be_saved()
    {
        Event::fake();

        $this->assertNull(Facades\Redirect::find('abc'));

        $redirect = Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('https://cool-runnings.com/old-url')
            ->destinationUrl('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true);

        $redirect->save();

        $this->assertInstanceOf(Redirect::class, $redirect = Facades\Redirect::find('abc'));
        $this->assertEquals('abc', $redirect->id());
        $this->assertFileExists($redirect->path());
        $this->assertStringContainsString('content/seo-pro/redirects/abc.yaml', $redirect->path());

        $this->assertStringEqualsStringIgnoringLineEndings(<<<'YAML'
source_url: 'https://cool-runnings.com/old-url'
destination_url: 'https://cool-runnings.com/new-url'
response_code: 302
enabled: true

YAML, file_get_contents($redirect->path()));

        Event::assertDispatched(RedirectCreated::class, fn ($event) => $event->redirect->id() === $redirect->id());
        Event::assertDispatched(RedirectSaved::class, fn ($event) => $event->redirect->id() === $redirect->id());
    }

    #[Test]
    public function redirect_can_be_deleted()
    {
        Event::fake();

        $redirect = Facades\Redirect::make()
            ->id('abc')
            ->sourceUrl('https://cool-runnings.com/old-url')
            ->destinationUrl('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true);

        $redirect->save();

        $this->assertNotNull(Facades\Redirect::find('abc'));

        $redirect->delete();

        $this->assertNull(Facades\Redirect::find('abc'));

        Event::assertDispatched(RedirectDeleted::class, fn ($event) => $event->redirect->id() === $redirect->id());
    }
}
