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
    public function it_creates_a_link_and_reference_when_scanning_a_subject()
    {
        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'See https://example.com/page for details.',
        ], null, 'My Entry');

        $this->assertCount(1, Facades\ExternalLink::all());

        $link = Facades\ExternalLink::all()->first();
        $this->assertEquals('https://example.com/page', $link->url());
        $this->assertCount(1, $link->references());

        $reference = $link->references()->first();
        $this->assertEquals('entry', $reference['subject_type']);
        $this->assertEquals('1', $reference['subject_id']);
        $this->assertEquals('My Entry', $reference['title']);
        $this->assertArrayNotHasKey('edit_url', $reference);
    }

    #[Test]
    public function it_tracks_the_same_url_separately_for_each_site()
    {
        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'See https://example.com/page for details.',
        ], null, 'My Entry');

        ContentScanner::syncForSubject('entry', '1-fr', 'fr', [
            'body' => 'Voir https://example.com/page pour plus de détails.',
        ], null, 'Mon Entrée');

        $this->assertEquals(['en', 'fr'], Facades\ExternalLink::all()->map->site()->sort()->values()->all());
        $this->assertEquals('My Entry', Facades\ExternalLink::query()->where('site', 'en')->first()->references()->first()['title']);
        $this->assertEquals('Mon Entrée', Facades\ExternalLink::query()->where('site', 'fr')->first()->references()->first()['title']);
    }

    #[Test]
    public function it_removes_stale_references_and_orphaned_links_when_content_changes()
    {
        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'See https://example.com/page for details.',
        ], null, 'My Entry');

        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'No links here any more.',
        ], null, 'My Entry');

        $this->assertCount(0, Facades\ExternalLink::all());
    }

    #[Test]
    public function it_keeps_a_link_when_another_subject_still_references_it()
    {
        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'https://shared.example.com',
        ], null, 'Entry One');

        ContentScanner::syncForSubject('entry', '2', 'en', [
            'body' => 'https://shared.example.com',
        ], null, 'Entry Two');

        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'No links.',
        ], null, 'Entry One');

        $this->assertCount(1, Facades\ExternalLink::all());

        $link = Facades\ExternalLink::all()->first();
        $this->assertCount(1, $link->references());
        $this->assertEquals('2', $link->references()->first()['subject_id']);
    }

    #[Test]
    public function it_removes_all_references_and_orphaned_links_when_a_subject_is_deleted()
    {
        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'https://example.com/gone',
        ], null, 'My Entry');

        ContentScanner::deleteForSubject('entry', '1', 'en');

        $this->assertCount(0, Facades\ExternalLink::all());
    }

    #[Test]
    public function it_updates_the_reference_title_without_duplicating_it()
    {
        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'https://example.com/page',
        ], null, 'Old Title');

        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'https://example.com/page',
        ], null, 'New Title');

        $link = Facades\ExternalLink::all()->first();

        $this->assertCount(1, $link->references());
        $this->assertEquals('New Title', $link->references()->first()['title']);
    }
}
