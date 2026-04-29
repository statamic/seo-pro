<?php

namespace Tests\Redirects;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class PurgeErrorsCommandTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function it_purges_errors_older_than_configured_threshold()
    {
        Carbon::setTestNow('2026-04-21 12:00:00');

        Facades\Error::make()
            ->id('old-error')
            ->url('/old-page')
            ->hits(5)
            ->lastHitAt('2026-03-01 12:00:00')
            ->save();

        Facades\Error::make()
            ->id('recent-error')
            ->url('/recent-page')
            ->hits(2)
            ->lastHitAt('2026-04-20 12:00:00')
            ->save();

        $this
            ->artisan('statamic:seo-pro:purge-errors')
            ->assertSuccessful();

        $this->assertNull(Facades\Error::find('old-error'));
        $this->assertNotNull(Facades\Error::find('recent-error'));
    }

    #[Test]
    public function it_uses_custom_purge_threshold()
    {
        Carbon::setTestNow('2026-04-21 12:00:00');

        config(['statamic.seo-pro.redirects.errors.purge_after_days' => 7]);

        Facades\Error::make()
            ->id('week-old')
            ->url('/week-old-page')
            ->hits(1)
            ->lastHitAt('2026-04-10 12:00:00')
            ->save();

        Facades\Error::make()
            ->id('recent')
            ->url('/recent-page')
            ->hits(1)
            ->lastHitAt('2026-04-20 12:00:00')
            ->save();

        $this
            ->artisan('statamic:seo-pro:purge-errors')
            ->assertSuccessful();

        $this->assertNull(Facades\Error::find('week-old'));
        $this->assertNotNull(Facades\Error::find('recent'));
    }

    #[Test]
    public function it_does_not_purge_errors_within_threshold()
    {
        Carbon::setTestNow('2026-04-21 12:00:00');

        Facades\Error::make()
            ->id('recent-one')
            ->url('/page-one')
            ->hits(1)
            ->lastHitAt('2026-04-15 12:00:00')
            ->save();

        Facades\Error::make()
            ->id('recent-two')
            ->url('/page-two')
            ->hits(3)
            ->lastHitAt('2026-04-20 12:00:00')
            ->save();

        $this
            ->artisan('statamic:seo-pro:purge-errors')
            ->assertSuccessful();

        $this->assertNotNull(Facades\Error::find('recent-one'));
        $this->assertNotNull(Facades\Error::find('recent-two'));
    }
}
