<?php

namespace Tests\Redirects;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\RecordRedirectHit;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class RecordRedirectHitTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function it_increments_the_hit_count()
    {
        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->assertEquals(0, Facades\Redirect::find('abc')->hits());

        (new RecordRedirectHit('abc'))->handle();

        $this->assertEquals(1, Facades\Redirect::find('abc')->hits());

        (new RecordRedirectHit('abc'))->handle();

        $this->assertEquals(2, Facades\Redirect::find('abc')->hits());
    }

    #[Test]
    public function it_records_the_last_hit_timestamp()
    {
        Carbon::setTestNow('2026-04-21 12:00:00');

        Facades\Redirect::make()
            ->id('abc')
            ->source('/old-url')
            ->destination('/new-url')
            ->responseCode(301)
            ->enabled(true)
            ->save();

        $this->assertNull(Facades\Redirect::find('abc')->lastHitAt());

        (new RecordRedirectHit('abc'))->handle();

        $this->assertEquals('2026-04-21 12:00:00', Facades\Redirect::find('abc')->lastHitAt());

        Carbon::setTestNow('2026-04-21 14:30:00');

        (new RecordRedirectHit('abc'))->handle();

        $this->assertEquals('2026-04-21 14:30:00', Facades\Redirect::find('abc')->lastHitAt());
    }

    #[Test]
    public function it_handles_missing_redirects_gracefully()
    {
        (new RecordRedirectHit('nonexistent'))->handle();

        $this->assertNull(Facades\Redirect::find('nonexistent'));
    }
}
