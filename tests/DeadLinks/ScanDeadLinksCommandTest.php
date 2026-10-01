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

class ScanDeadLinksCommandTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function it_scans_entries_terms_and_global_sets_for_links()
    {
        Blueprint::make('article')->setContents([
            'fields' => [
                ['handle' => 'title', 'field' => ['type' => 'text']],
                ['handle' => 'body', 'field' => ['type' => 'textarea']],
            ],
        ])->setNamespace('collections.blog')->save();

        Blueprint::make('tag')->setContents([
            'fields' => [
                ['handle' => 'title', 'field' => ['type' => 'text']],
                ['handle' => 'source', 'field' => ['type' => 'text']],
            ],
        ])->setNamespace('taxonomies.tags')->save();

        Blueprint::make('footer')->setContents([
            'fields' => [
                ['handle' => 'social_url', 'field' => ['type' => 'text']],
            ],
        ])->setNamespace('globals.footer')->save();

        Collection::make('blog')->save();
        Taxonomy::make('tags')->save();

        Entry::make()
            ->collection('blog')
            ->blueprint('article')
            ->slug('hello-world')
            ->data(['title' => 'Hello World', 'body' => 'Read more at https://example.com/hello'])
            ->save();

        Term::make()
            ->taxonomy('tags')
            ->slug('news')
            ->data(['title' => 'News', 'source' => 'https://example.com/news'])
            ->save();

        $globalSet = tap(GlobalSet::make('footer')->title('Footer'))->save();

        $globalSet->makeLocalization('default')
            ->data(['social_url' => 'https://example.com/social'])
            ->save();

        $this->assertCount(0, DeadLink::all());

        $this->artisan('statamic:seo-pro:scan-dead-links')->assertSuccessful();

        $this->assertEquals(
            ['entry', 'global', 'term'],
            DeadLink::all()->flatMap->references()->pluck('subject_type')->sort()->values()->all()
        );
    }
}
