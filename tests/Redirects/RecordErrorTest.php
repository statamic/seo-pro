<?php

namespace Tests\Redirects;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\RecordError;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class RecordErrorTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function it_creates_an_error_for_a_new_url()
    {
        Carbon::setTestNow('2026-04-21 12:00:00');

        $this->assertNull(Facades\Error::query()->where('url', '/missing-page')->first());

        (new RecordError('/missing-page'))->handle();

        $error = Facades\Error::query()->where('url', '/missing-page')->first();

        $this->assertNotNull($error);
        $this->assertEquals('/missing-page', $error->url());
        $this->assertEquals(1, $error->hits());
        $this->assertEquals('2026-04-21 12:00:00', $error->lastHitAt());
    }

    #[Test]
    public function it_increments_the_hit_count_for_an_existing_url()
    {
        Facades\Error::make()
            ->id('abc')
            ->url('/missing-page')
            ->hits(3)
            ->lastHitAt('2026-04-20 10:00:00')
            ->save();

        (new RecordError('/missing-page'))->handle();

        $this->assertEquals(4, Facades\Error::find('abc')->hits());
    }

    #[Test]
    public function it_updates_the_last_hit_timestamp()
    {
        Carbon::setTestNow('2026-04-21 14:30:00');

        Facades\Error::make()
            ->id('abc')
            ->url('/missing-page')
            ->hits(1)
            ->lastHitAt('2026-04-20 10:00:00')
            ->save();

        (new RecordError('/missing-page'))->handle();

        $this->assertEquals('2026-04-21 14:30:00', Facades\Error::find('abc')->lastHitAt());
    }
}
