<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\SeoPro\BrokenLinks\ContentScanner;

class ScanBrokenLinksCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:scan-broken-links';

    protected $description = 'Scan all entries, terms, and global sets for external links';

    public function handle(): int
    {
        $count = 0;

        $count += $this->scanEntries();
        $count += $this->scanTerms();
        $count += $this->scanGlobals();

        $this->components->info("Scanned {$count} item(s) for external links.");

        return self::SUCCESS;
    }

    private function scanEntries(): int
    {
        $count = 0;

        foreach (Collection::all() as $collection) {
            foreach ($collection->sites() as $siteHandle) {
                $entries = Entry::query()
                    ->where('collection', $collection->handle())
                    ->where('site', $siteHandle)
                    ->lazy();

                foreach ($entries as $entry) {
                    ContentScanner::scan(
                        type: 'entry',
                        id: $entry->id(),
                        values: $entry->data()->all(),
                        site: $entry->locale(),
                        blueprint: $entry->blueprint(),
                    );

                    $count++;
                }
            }
        }

        return $count;
    }

    private function scanTerms(): int
    {
        $count = 0;

        foreach (Taxonomy::all() as $taxonomy) {
            foreach ($taxonomy->sites() as $siteHandle) {
                $terms = Term::query()
                    ->where('taxonomy', $taxonomy->handle())
                    ->where('site', $siteHandle)
                    ->lazy();

                foreach ($terms as $term) {
                    ContentScanner::scan(
                        type: 'term',
                        id: $term->id(),
                        values: $term->data()->all(),
                        site: $term->locale(),
                        blueprint: $term->blueprint(),
                    );

                    $count++;
                }
            }
        }

        return $count;
    }

    private function scanGlobals(): int
    {
        $count = 0;

        foreach (GlobalSet::all() as $globalSet) {
            foreach ($globalSet->sites() as $siteHandle) {
                if (! $variables = $globalSet->in($siteHandle)) {
                    continue;
                }

                ContentScanner::scan(
                    type: 'global',
                    id: $variables->handle(),
                    values: $variables->data()->all(),
                    site: $variables->locale(),
                    blueprint: $variables->blueprint(),
                );

                $count++;
            }
        }

        return $count;
    }
}
