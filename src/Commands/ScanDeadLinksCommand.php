<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\SeoPro\DeadLinks\ContentScanner;

class ScanDeadLinksCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:scan-dead-links';

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

    protected function scanEntries(): int
    {
        $count = 0;

        foreach (Collection::all() as $collection) {
            foreach ($collection->sites() as $siteHandle) {
                $entries = Entry::query()
                    ->where('collection', $collection->handle())
                    ->where('site', $siteHandle)
                    ->get();

                foreach ($entries as $entry) {
                    ContentScanner::syncEntry($entry);

                    $count++;
                }
            }
        }

        return $count;
    }

    protected function scanTerms(): int
    {
        $count = 0;

        foreach (Taxonomy::all() as $taxonomy) {
            foreach ($taxonomy->sites() as $siteHandle) {
                $terms = Term::query()
                    ->where('taxonomy', $taxonomy->handle())
                    ->where('site', $siteHandle)
                    ->get();

                foreach ($terms as $term) {
                    ContentScanner::syncTerm($term);

                    $count++;
                }
            }
        }

        return $count;
    }

    protected function scanGlobals(): int
    {
        $count = 0;

        foreach (GlobalSet::all() as $globalSet) {
            foreach ($globalSet->sites() as $siteHandle) {
                if (! $variables = $globalSet->in($siteHandle)) {
                    continue;
                }

                ContentScanner::syncGlobalVariables($variables);

                $count++;
            }
        }

        return $count;
    }
}
