<?php

namespace Tests\BrokenLinks;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\BrokenLinks\ContentScanner;
use Statamic\SeoPro\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ContentScannerTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function it_creates_a_link_and_reference_when_scanning_an_item()
    {
        ContentScanner::scan(type: 'entry', id: '1', site: 'en', title: 'My Entry', values: [
            'body' => 'See https://example.com/page for details.',
        ]);

        $this->assertCount(1, Facades\ExternalLink::all());

        $link = Facades\ExternalLink::all()->first();
        $this->assertEquals('https://example.com/page', $link->url());
        $this->assertCount(1, $link->references());

        $reference = $link->references()->first();
        $this->assertEquals('entry', $reference->type);
        $this->assertEquals('1', $reference->id);
        $this->assertEquals('My Entry', $reference->title);
    }

    #[Test]
    public function it_tracks_the_same_url_separately_for_each_site()
    {
        ContentScanner::scan(type: 'entry', id: '1', site: 'en', title: 'My Entry', values: [
            'body' => 'See https://example.com/page for details.',
        ]);

        ContentScanner::scan(type: 'entry', id: '1-fr', site: 'fr', title: 'Mon Entrée', values: [
            'body' => 'Voir https://example.com/page pour plus de détails.',
        ]);

        $this->assertEquals(['en', 'fr'], Facades\ExternalLink::all()->map->site()->sort()->values()->all());
        $this->assertEquals('My Entry', Facades\ExternalLink::query()->where('site', 'en')->first()->references()->first()->title);
        $this->assertEquals('Mon Entrée', Facades\ExternalLink::query()->where('site', 'fr')->first()->references()->first()->title);
    }

    #[Test]
    public function it_removes_stale_references_and_orphaned_links_when_content_changes()
    {
        ContentScanner::scan(type: 'entry', id: '1', site: 'en', title: 'My Entry', values: [
            'body' => 'See https://example.com/page for details.',
        ]);

        ContentScanner::scan(type: 'entry', id: '1', site: 'en', title: 'My Entry', values: [
            'body' => 'No links here any more.',
        ]);

        $this->assertCount(0, Facades\ExternalLink::all());
    }

    #[Test]
    public function it_keeps_a_link_when_another_item_still_references_it()
    {
        ContentScanner::scan(type: 'entry', id: '1', site: 'en', title: 'Entry One', values: [
            'body' => 'https://shared.example.com',
        ]);

        ContentScanner::scan(type: 'entry', id: '2', site: 'en', title: 'Entry Two', values: [
            'body' => 'https://shared.example.com',
        ]);

        ContentScanner::scan(type: 'entry', id: '1', site: 'en', title: 'Entry One', values: [
            'body' => 'No links.',
        ]);

        $this->assertCount(1, Facades\ExternalLink::all());

        $link = Facades\ExternalLink::all()->first();
        $this->assertCount(1, $link->references());
        $this->assertEquals('2', $link->references()->first()->id);
    }

    #[Test]
    public function it_removes_all_references_and_orphaned_links_when_an_item_is_forgotten()
    {
        ContentScanner::scan(type: 'entry', id: '1', site: 'en', title: 'My Entry', values: [
            'body' => 'https://example.com/gone',
        ]);

        ContentScanner::forget(type: 'entry', id: '1', site: 'en');

        $this->assertCount(0, Facades\ExternalLink::all());
    }

    #[Test]
    public function it_updates_the_reference_title_without_duplicating_it()
    {
        ContentScanner::scan(type: 'entry', id: '1', site: 'en', title: 'Old Title', values: [
            'body' => 'https://example.com/page',
        ]);

        ContentScanner::scan(type: 'entry', id: '1', site: 'en', title: 'New Title', values: [
            'body' => 'https://example.com/page',
        ]);

        $link = Facades\ExternalLink::all()->first();

        $this->assertCount(1, $link->references());
        $this->assertEquals('New Title', $link->references()->first()->title);
    }
}
