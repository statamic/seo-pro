<?php

namespace Statamic\SeoPro\BrokenLinks;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Globals\Variables;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\Facades;
use Statamic\Taxonomies\LocalizedTerm;

class ContentScanner
{
    /**
     * Extract links from the given subject's field values, and reconcile
     * stored references so they match reality: for every link still found,
     * this subject's references on it are rebuilt from scratch; for every
     * link that *was* referenced by this subject but no longer is, the
     * reference is dropped (and the link deleted entirely if that was its
     * last reference anywhere).
     */
    public static function syncForSubject(
        string $subjectType,
        string $subjectId,
        string $site,
        array $values,
        ?Blueprint $blueprint,
        string $title
    ): void {
        $found = LinkExtractor::extract($values, $blueprint)->groupBy('url');

        foreach ($found as $url => $rows) {
            $link = Facades\ExternalLink::findByUrl($url, $site) ?? Facades\ExternalLink::make()
                ->site($site)
                ->url($url)
                ->nextCheckAt(now());

            $otherSubjectsReferences = $link->references()->reject(
                fn ($reference) => self::referencesSubject($reference, $subjectType, $subjectId, $site)
            );

            $thisSubjectsReferences = $rows->map(fn ($row) => [
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'site' => $site,
                'field_path' => $row['field_path'],
                'title' => $title,
            ]);

            $link->references($otherSubjectsReferences->concat($thisSubjectsReferences)->values()->all())->save();
        }

        self::removeStaleReferences($subjectType, $subjectId, $site, except: $found->keys()->all());
    }

    public static function syncEntry(Entry $entry): void
    {
        self::syncForSubject(
            'entry',
            (string) $entry->id(),
            (string) $entry->locale(),
            $entry->data()->all(),
            $entry->blueprint(),
            (string) ($entry->get('title') ?? $entry->id())
        );
    }

    public static function syncTerm(LocalizedTerm $term): void
    {
        self::syncForSubject(
            'term',
            (string) $term->id(),
            (string) $term->locale(),
            $term->data()->all(),
            $term->blueprint(),
            (string) ($term->get('title') ?? $term->slug())
        );
    }

    public static function syncGlobalVariables(Variables $variables): void
    {
        self::syncForSubject(
            'global',
            (string) $variables->handle(),
            (string) $variables->locale(),
            $variables->data()->all(),
            $variables->blueprint(),
            (string) $variables->title()
        );
    }

    /**
     * Remove every reference belonging to a deleted subject, cleaning up
     * any link that's now unreferenced anywhere.
     */
    public static function deleteForSubject(string $subjectType, string $subjectId, string $site): void
    {
        self::removeStaleReferences($subjectType, $subjectId, $site, except: []);
    }

    private static function removeStaleReferences(string $subjectType, string $subjectId, string $site, array $except): void
    {
        Facades\ExternalLink::query()
            ->whereJsonContains('subjects', ExternalLink::subjectKey($subjectType, $subjectId, $site))
            ->get()
            ->reject(fn (ExternalLink $link) => in_array($link->url(), $except))
            ->each(function (ExternalLink $link) use ($subjectType, $subjectId, $site) {
                $remaining = $link->references()->reject(
                    fn ($reference) => self::referencesSubject($reference, $subjectType, $subjectId, $site)
                );

                if ($remaining->isEmpty()) {
                    $link->delete();

                    return;
                }

                $link->references($remaining->values()->all())->save();
            });
    }

    private static function referencesSubject(array $reference, string $subjectType, string $subjectId, string $site): bool
    {
        return $reference['subject_type'] === $subjectType
            && $reference['subject_id'] === $subjectId
            && $reference['site'] === $site;
    }
}
