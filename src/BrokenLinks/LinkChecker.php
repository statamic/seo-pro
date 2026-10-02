<?php

namespace Statamic\SeoPro\BrokenLinks;

use Carbon\CarbonInterval;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Notifications\BrokenLinksDigest;
use Throwable;

class LinkChecker
{
    /**
     * Check the most overdue links, then send a digest email of anything
     * newly broken (if there are recipients). Returns how many links were checked.
     */
    public static function checkDue(): int
    {
        $links = Facades\ExternalLink::query()
            ->where('next_check_at', '<=', now())
            ->orderBy('next_check_at')
            ->limit(config('statamic.seo-pro.broken_links.check.batch_size', 100))
            ->get();

        if ($links->isEmpty()) {
            return 0;
        }

        self::checkLinks($links);
        self::notifyIfNeeded();

        return $links->count();
    }

    public static function checkLinks(Collection $links): void
    {
        $timeout = config('statamic.seo-pro.broken_links.check.timeout', 10);
        $userAgent = config('statamic.seo-pro.broken_links.check.user_agent', 'Mozilla/5.0 (compatible; SeoProLinkChecker/1.0; +https://statamic.com)');
        $concurrency = max(1, (int) config('statamic.seo-pro.broken_links.check.concurrency', 10));

        $links->chunk($concurrency)->each(function (Collection $chunk) use ($timeout, $userAgent) {
            $results = self::request($chunk, 'head', $timeout, $userAgent);

            $needsRetry = $chunk->filter(fn ($link) => self::shouldRetryWithGet($results[(string) $link->id()] ?? null))->values();

            if ($needsRetry->isNotEmpty()) {
                $results = $results->merge(self::request($needsRetry, 'get', $timeout, $userAgent));
            }

            foreach ($chunk as $link) {
                self::applyResult($link, $results[(string) $link->id()] ?? null);
            }
        });
    }

    private static function request(Collection $links, string $method, int $timeout, string $userAgent): Collection
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

    private static function shouldRetryWithGet($response): bool
    {
        return $response instanceof Response && in_array($response->status(), [403, 405, 501]);
    }

    private static function applyResult(ExternalLink $link, $response): void
    {
        if (! $link = Facades\ExternalLink::find($link->id())) {
            return;
        }

        if ($response instanceof Response) {
            $ok = $response->status() >= 200 && $response->status() < 400;
            $link->statusCode($response->status());
            $link->error(null);
        } else {
            $ok = false;
            $link->statusCode(null);
            $link->error(self::errorFor($response));
        }

        $link->status($ok ? ExternalLink::STATUS_OK : ExternalLink::STATUS_FAILING);
        $link->checkedAt(now());
        $link->nextCheckAt(now()->add(self::frequencyInterval()));

        if ($ok) {
            $link->failingSince(null)->notifiedAt(null);
        } elseif (! $link->failingSince()) {
            $link->failingSince(now());
        }

        $link->save();
    }

    private static function errorFor($response): string
    {
        $message = $response instanceof Throwable ? $response->getMessage() : '';

        preg_match('/cURL error (\d+)/', $message, $matches);

        return match ((int) ($matches[1] ?? 0)) {
            6 => 'host_not_found',
            7 => 'connection_refused',
            28 => 'timed_out',
            35, 51, 53, 58, 60 => 'ssl_error',
            default => 'unreachable',
        };
    }

    private static function frequencyInterval(): CarbonInterval
    {
        $minutes = [
            'every_15_minutes' => 15,
            'every_30_minutes' => 30,
            'hourly' => 60,
            'every_6_hours' => 60 * 6,
            'every_12_hours' => 60 * 12,
            'daily' => 60 * 24,
            'weekly' => 60 * 24 * 7,
        ][config('statamic.seo-pro.broken_links.check.frequency', 'hourly')] ?? 60;

        return CarbonInterval::minutes($minutes);
    }

    /**
     * Email a single collated digest of every link that has been broken
     * for at least a day, and hasn't already been notified about.
     */
    public static function notifyIfNeeded(): void
    {
        $recipients = config('statamic.seo-pro.broken_links.notifications.recipients', []);

        if (empty($recipients)) {
            return;
        }

        $links = Facades\ExternalLink::query()
            ->where('failing_since', '<=', now()->subDay())
            ->whereNull('notified_at')
            ->get();

        if ($links->isEmpty()) {
            return;
        }

        Notification::route('mail', $recipients)->notify(new BrokenLinksDigest($links));

        $links->each(fn (ExternalLink $link) => $link->notifiedAt(now())->save());
    }
}
