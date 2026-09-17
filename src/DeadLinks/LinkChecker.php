<?php

namespace Statamic\SeoPro\DeadLinks;

use Carbon\CarbonInterval;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Statamic\SeoPro\Facades\DeadLink;
use Statamic\SeoPro\Notifications\DeadLinksDigest;
use Throwable;

class LinkChecker
{
    /**
     * Check every link that is due, then send a digest email of anything
     * newly broken (if enabled). Returns how many links were checked.
     */
    public static function checkDue(): int
    {
        $batchSize = config('statamic.seo-pro.dead_links.check.batch_size', 100);

        $links = DeadLink::query()
            ->where('next_check_at', '<=', now())
            ->orWhereNull('next_check_at')
            ->get()
            ->sortBy(fn (Link $link) => $link->nextCheckAt() ?? now())
            ->take($batchSize)
            ->values();

        if ($links->isEmpty()) {
            return 0;
        }

        static::checkLinks($links);
        static::notifyIfNeeded();

        return $links->count();
    }

    public static function checkLinks(Collection $links): void
    {
        $timeout = config('statamic.seo-pro.dead_links.check.timeout', 10);
        $userAgent = config('statamic.seo-pro.dead_links.check.user_agent');
        $concurrency = max(1, (int) config('statamic.seo-pro.dead_links.check.concurrency', 10));

        $links->chunk($concurrency)->each(function (Collection $chunk) use ($timeout, $userAgent) {
            $results = static::request($chunk, 'head', $timeout, $userAgent);

            $needsRetry = $chunk->filter(fn ($link) => static::shouldRetryWithGet($results[(string) $link->id()] ?? null))->values();

            if ($needsRetry->isNotEmpty()) {
                $results = $results->merge(static::request($needsRetry, 'get', $timeout, $userAgent));
            }

            foreach ($chunk as $link) {
                static::applyResult($link, $results[(string) $link->id()] ?? null);
            }
        });
    }

    protected static function request(Collection $links, string $method, int $timeout, string $userAgent): Collection
    {
        $responses = Http::pool(function ($pool) use ($links, $method, $timeout, $userAgent) {
            return $links->map(function ($link) use ($pool, $method, $timeout, $userAgent) {
                return $pool->as((string) $link->id())
                    ->withHeaders(['User-Agent' => $userAgent])
                    ->timeout($timeout)
                    ->connectTimeout(min($timeout, 5))
                    ->withOptions(['allow_redirects' => ['max' => 5]])
                    ->{$method}($link->url());
            })->all();
        });

        return collect($responses);
    }

    protected static function shouldRetryWithGet($response): bool
    {
        if ($response instanceof Response) {
            return in_array($response->status(), [403, 405, 501]);
        }

        return true;
    }

    protected static function applyResult(Link $link, $response): void
    {
        $wasFailing = $link->isFailing();

        if ($response instanceof Response) {
            $ok = $response->status() >= 200 && $response->status() < 400;
            $link->statusCode($response->status());
            $link->error(null);
        } else {
            $ok = false;
            $link->statusCode(null);
            $link->error($response instanceof Throwable
                ? Str::limit($response->getMessage(), 250)
                : 'Request failed');
        }

        $link->status($ok ? Link::STATUS_OK : Link::STATUS_FAILING);
        $link->consecutiveFailures($ok ? 0 : $link->consecutiveFailures() + 1);
        $link->checkedAt(now());
        $link->nextCheckAt(now()->add(static::frequencyInterval()));

        if ($ok && $wasFailing) {
            $link->notifiedAt(null);
        }

        $link->save();
    }

    protected static function frequencyInterval(): CarbonInterval
    {
        $minutes = [
            'every_15_minutes' => 15,
            'every_30_minutes' => 30,
            'hourly' => 60,
            'every_6_hours' => 60 * 6,
            'every_12_hours' => 60 * 12,
            'daily' => 60 * 24,
            'weekly' => 60 * 24 * 7,
        ][config('statamic.seo-pro.dead_links.check.frequency', 'hourly')] ?? 60;

        return CarbonInterval::minutes($minutes);
    }

    /**
     * Email a single collated digest of every link that is currently
     * failing and hasn't already been notified about since it last failed.
     */
    public static function notifyIfNeeded(): void
    {
        if (! config('statamic.seo-pro.dead_links.notifications.enabled', false)) {
            return;
        }

        $recipients = config('statamic.seo-pro.dead_links.notifications.recipients', []);

        if (empty($recipients)) {
            return;
        }

        $links = DeadLink::query()
            ->where('status', Link::STATUS_FAILING)
            ->whereNull('notified_at')
            ->get();

        if ($links->isEmpty()) {
            return;
        }

        Notification::route('mail', $recipients)->notify(new DeadLinksDigest($links));

        $links->each(fn (Link $link) => $link->notifiedAt(now())->save());
    }
}
