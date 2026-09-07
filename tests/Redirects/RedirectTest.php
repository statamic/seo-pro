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
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true);

        $redirect->save();

        $this->assertInstanceOf(Redirect::class, $redirect = Facades\Redirect::find('abc'));
        $this->assertEquals('abc', $redirect->id());
        $this->assertFileExists($redirect->path());
        $this->assertStringContainsString('content/seo-pro/redirects/abc.yaml', $redirect->path());

        $this->assertStringEqualsStringIgnoringLineEndings(<<<'YAML'
source: 'https://cool-runnings.com/old-url'
destination: 'https://cool-runnings.com/new-url'
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
            ->source('https://cool-runnings.com/old-url')
            ->destination('https://cool-runnings.com/new-url')
            ->responseCode(302)
            ->enabled(true);

        $redirect->save();

        $this->assertNotNull(Facades\Redirect::find('abc'));

        $redirect->delete();

        $this->assertNull(Facades\Redirect::find('abc'));

        Event::assertDispatched(RedirectDeleted::class, fn ($event) => $event->redirect->id() === $redirect->id());
    }

    #[Test]
    public function creating_a_redirect_deletes_errors_with_matching_url()
    {
        Facades\Error::make()
            ->id('error-one')
            ->url('/old-url')
            ->hits(5)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();

        Facades\Error::make()
            ->id('error-two')
            ->url('/other-url')
            ->hits(2)
            ->lastHitAt('2026-04-21 12:00:00')
            ->save();

        $this->assertNotNull(Facades\Error::find('error-one'));
        $this->assertNotNull(Facades\Error::find('error-two'));

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->assertNull(Facades\Error::find('error-one'));
        $this->assertNotNull(Facades\Error::find('error-two'));
    }
}
