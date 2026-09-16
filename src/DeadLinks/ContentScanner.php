<?php

namespace Statamic\SeoPro\DeadLinks;

use Statamic\Fields\Blueprint;
use Statamic\SeoPro\Facades\DeadLink;

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
        string $title,
        ?string $editUrl
    ): void {
        $found = LinkExtractor::extract($values, $blueprint)->groupBy('url');

        foreach ($found as $url => $rows) {
            $link = DeadLink::query()->where('url', $url)->first() ?? DeadLink::make()
                ->url($url)
                ->status(Link::STATUS_PENDING)
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
                'edit_url' => $editUrl,
            ]);

            $link->references($otherSubjectsReferences->concat($thisSubjectsReferences)->values()->all())->save();
        }

        self::removeStaleReferences($subjectType, $subjectId, $site, except: $found->keys()->all());
    }

    /**
     * Remove every reference belonging to a deleted subject, cleaning up
     * any link that's now unreferenced anywhere.
     */
    public static function deleteForSubject(string $subjectType, string $subjectId, string $site): void
    {
        self::removeStaleReferences($subjectType, $subjectId, $site, except: []);
    }

    protected static function removeStaleReferences(string $subjectType, string $subjectId, string $site, array $except): void
    {
        DeadLink::query()->get()
            ->reject(fn (Link $link) => in_array($link->url(), $except))
            ->filter(fn (Link $link) => $link->references()->contains(
                fn ($reference) => self::referencesSubject($reference, $subjectType, $subjectId, $site)
            ))
            ->each(function (Link $link) use ($subjectType, $subjectId, $site) {
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

    protected static function referencesSubject(array $reference, string $subjectType, string $subjectId, string $site): bool
    {
        return $reference['subject_type'] === $subjectType
            && $reference['subject_id'] === $subjectId
            && $reference['site'] === $site;
    }
}
