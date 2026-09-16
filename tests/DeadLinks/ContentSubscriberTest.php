<?php

namespace Tests\DeadLinks;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\SeoPro\Facades\DeadLink;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ContentSubscriberTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Setting only `enabled` here would work fine on its own, but since
        // ServiceProvider::bootAddonConfig() later merges config/seo-pro.php
        // with array_merge (not recursive), whichever keys we *don't* also
        // set here get dropped from the whole `dead_links` array - notably
        // `directory`, which the Stache store needs to be configured before
        // PreventsSavingStacheItemsToDisk redirects it for this test.
        $app['config']->set('statamic.seo-pro.dead_links.enabled', true);
        $app['config']->set('statamic.seo-pro.dead_links.directory', storage_path('statamic/seopro/dead-links'));
    }

    #[Test]
    public function it_syncs_links_automatically_when_an_entry_is_saved_and_cleans_up_on_delete()
    {
        Blueprint::make('article')->setContents([
            'fields' => [
                ['handle' => 'title', 'field' => ['type' => 'text']],
                ['handle' => 'body', 'field' => ['type' => 'textarea']],
            ],
        ])->setNamespace('collections.blog')->save();

        Collection::make('blog')->save();

        $entry = tap(Entry::make()
            ->collection('blog')
            ->blueprint('article')
            ->slug('hello-world')
            ->data([
                'title' => 'Hello World',
                'body' => 'Read more at https://example.com/hello',
            ]))->save();

        $this->assertCount(1, DeadLink::all());
        $this->assertEquals('https://example.com/hello', DeadLink::all()->first()->url());
        $this->assertEquals($entry->id(), DeadLink::all()->first()->references()->first()['subject_id']);

        $entry->delete();

        $this->assertCount(0, DeadLink::all());
    }

    #[Test]
    public function it_syncs_links_automatically_when_a_term_is_saved()
    {
        Blueprint::make('tag')->setContents([
            'fields' => [
                ['handle' => 'title', 'field' => ['type' => 'text']],
                ['handle' => 'source', 'field' => ['type' => 'text']],
            ],
        ])->setNamespace('taxonomies.tags')->save();

        Taxonomy::make('tags')->save();

        Term::make()
            ->taxonomy('tags')
            ->slug('news')
            ->data([
                'title' => 'News',
                'source' => 'https://example.com/news',
            ])
            ->save();

        $this->assertCount(1, DeadLink::all());
        $this->assertEquals('term', DeadLink::all()->first()->references()->first()['subject_type']);
    }

    #[Test]
    public function it_syncs_links_automatically_when_a_global_set_is_saved()
    {
        $globalSet = tap(GlobalSet::make('footer')->title('Footer'))->save();

        Blueprint::make('footer')->setContents([
            'fields' => [
                ['handle' => 'social_url', 'field' => ['type' => 'text']],
            ],
        ])->setNamespace('globals.footer')->save();

        $globalSet->makeLocalization('en')
            ->data(['social_url' => 'https://example.com/social'])
            ->save();

        $this->assertCount(1, DeadLink::all());
        $this->assertEquals('global', DeadLink::all()->first()->references()->first()['subject_type']);
    }
}
