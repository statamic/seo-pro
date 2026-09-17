<?php

namespace Tests\DeadLinks;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\DeadLinks\ContentScanner;
use Statamic\SeoPro\Facades\DeadLink;
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
        ], null, 'My Entry', '/cp/collections/blog/entries/1');

        $this->assertCount(1, DeadLink::all());

        $link = DeadLink::all()->first();
        $this->assertEquals('https://example.com/page', $link->url());
        $this->assertCount(1, $link->references());

        $reference = $link->references()->first();
        $this->assertEquals('entry', $reference['subject_type']);
        $this->assertEquals('1', $reference['subject_id']);
        $this->assertEquals('My Entry', $reference['title']);
    }

    #[Test]
    public function it_removes_stale_references_and_orphaned_links_when_content_changes()
    {
        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'See https://example.com/page for details.',
        ], null, 'My Entry', null);

        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'No links here any more.',
        ], null, 'My Entry', null);

        $this->assertCount(0, DeadLink::all());
    }

    #[Test]
    public function it_keeps_a_link_when_another_subject_still_references_it()
    {
        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'https://shared.example.com',
        ], null, 'Entry One', null);

        ContentScanner::syncForSubject('entry', '2', 'en', [
            'body' => 'https://shared.example.com',
        ], null, 'Entry Two', null);

        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'No links.',
        ], null, 'Entry One', null);

        $this->assertCount(1, DeadLink::all());

        $link = DeadLink::all()->first();
        $this->assertCount(1, $link->references());
        $this->assertEquals('2', $link->references()->first()['subject_id']);
    }

    #[Test]
    public function it_removes_all_references_and_orphaned_links_when_a_subject_is_deleted()
    {
        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'https://example.com/gone',
        ], null, 'My Entry', null);

        ContentScanner::deleteForSubject('entry', '1', 'en');

        $this->assertCount(0, DeadLink::all());
    }

    #[Test]
    public function it_updates_the_reference_title_and_edit_url_without_duplicating_it()
    {
        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'https://example.com/page',
        ], null, 'Old Title', '/old-url');

        ContentScanner::syncForSubject('entry', '1', 'en', [
            'body' => 'https://example.com/page',
        ], null, 'New Title', '/new-url');

        $link = DeadLink::all()->first();

        $this->assertCount(1, $link->references());
        $this->assertEquals('New Title', $link->references()->first()['title']);
        $this->assertEquals('/new-url', $link->references()->first()['edit_url']);
    }
}
