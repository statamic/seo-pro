<?php

namespace Tests\Redirects;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Events\ErrorCreated;
use Statamic\SeoPro\Events\ErrorDeleted;
use Statamic\SeoPro\Events\ErrorSaved;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Error;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ErrorTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function error_can_be_saved()
    {
        Event::fake();

        $this->assertNull(Facades\Error::find('abc'));

        $error = Facades\Error::make()
            ->id('abc')
            ->url('/broken-link')
            ->hits(3)
            ->lastHitAt('2026-04-21 12:00:00');

        $error->save();

        $this->assertInstanceOf(Error::class, $error = Facades\Error::find('abc'));
        $this->assertEquals('abc', $error->id());
        $this->assertEquals('/broken-link', $error->url());
        $this->assertEquals(3, $error->hits());
        $this->assertEquals('2026-04-21 12:00:00', $error->lastHitAt());
        $this->assertFileExists($error->path());
        $this->assertStringContainsString('errors/abc.yaml', $error->path());

        $this->assertStringEqualsStringIgnoringLineEndings(<<<'YAML'
url: /broken-link
hits: 3
last_hit_at: '2026-04-21 12:00:00'

YAML, file_get_contents($error->path()));

        Event::assertDispatched(ErrorCreated::class, fn ($event) => $event->error->id() === $error->id());
        Event::assertDispatched(ErrorSaved::class, fn ($event) => $event->error->id() === $error->id());
    }

    #[Test]
    public function error_can_be_deleted()
    {
        Event::fake();

        $error = Facades\Error::make()
            ->id('abc')
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00');

        $error->save();

        $this->assertNotNull(Facades\Error::find('abc'));

        $error->delete();

        $this->assertNull(Facades\Error::find('abc'));

        Event::assertDispatched(ErrorDeleted::class, fn ($event) => $event->error->id() === $error->id());
    }

    #[Test]
    public function saving_existing_error_does_not_dispatch_created_event()
    {
        Event::fake();

        $error = Facades\Error::make()
            ->id('abc')
            ->url('/broken-link')
            ->hits(1)
            ->lastHitAt('2026-04-21 12:00:00');

        $error->save();

        Event::assertDispatchedTimes(ErrorCreated::class, 1);

        $error->hits(2)->save();

        Event::assertDispatchedTimes(ErrorCreated::class, 1);
        Event::assertDispatchedTimes(ErrorSaved::class, 2);
    }
}
