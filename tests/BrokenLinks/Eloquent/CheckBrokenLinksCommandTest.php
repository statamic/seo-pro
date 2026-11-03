<?php

namespace Tests\BrokenLinks\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\Facades;
use Tests\TestCase;

class CheckBrokenLinksCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.seo-pro.broken_links.enabled', true);
        $app['config']->set('statamic.seo-pro.broken_links.driver', 'database');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../src/Commands/stubs');
    }

    #[Test]
    public function it_checks_the_most_overdue_links_first_up_to_the_batch_size()
    {
        Http::fake(['*' => Http::response()]);

        config()->set('statamic.seo-pro.broken_links.check.batch_size', 2);

        $recent = $this->createLink('https://example.com/recent', now()->subMinute());
        $oldest = $this->createLink('https://example.com/oldest', now()->subDay());
        $older = $this->createLink('https://example.com/older', now()->subHour());
        $later = $this->createLink('https://example.com/later', now()->addHour());

        $this->artisan('statamic:seo-pro:check-broken-links')->expectsOutputToContain('Checked 2 link(s).');

        $this->assertNotNull(Facades\ExternalLink::find($oldest->id())->checkedAt());
        $this->assertNotNull(Facades\ExternalLink::find($older->id())->checkedAt());
        $this->assertNull(Facades\ExternalLink::find($recent->id())->checkedAt());
        $this->assertNull(Facades\ExternalLink::find($later->id())->checkedAt());
    }

    private function createLink(string $url, $nextCheckAt): ExternalLink
    {
        return tap(Facades\ExternalLink::make()->url($url)->nextCheckAt($nextCheckAt))->save();
    }
}
