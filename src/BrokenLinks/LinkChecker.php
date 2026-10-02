<?php

namespace Statamic\SeoPro\BrokenLinks;

use Carbon\CarbonInterval;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Statamic\SeoPro\Facades;
use Throwable;

class LinkChecker
{
    public function check(Collection $links): void
    {
        $timeout = config('statamic.seo-pro.broken_links.check.timeout', 10);
        $userAgent = config('statamic.seo-pro.broken_links.check.user_agent', 'Mozilla/5.0 (compatible; SeoProLinkChecker/1.0; +https://statamic.com)');
        $concurrency = max(1, (int) config('statamic.seo-pro.broken_links.check.concurrency', 10));

        $links->chunk($concurrency)->each(function (Collection $chunk) use ($timeout, $userAgent) {
            $results = $this->request($chunk, 'head', $timeout, $userAgent);

            $needsRetry = $chunk->filter(fn ($link) => $this->shouldRetryWithGet($results[(string) $link->id()] ?? null))->values();

            if ($needsRetry->isNotEmpty()) {
                $results = $results->merge($this->request($needsRetry, 'get', $timeout, $userAgent));
            }

            foreach ($chunk as $link) {
                $this->applyResult($link, $results[(string) $link->id()] ?? null);
            }
        });
    }

    private function request(Collection $links, string $method, int $timeout, string $userAgent): Collection
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

    private function shouldRetryWithGet($response): bool
    {
        return $response instanceof Response && in_array($response->status(), [403, 405, 501]);
    }

    private function applyResult(ExternalLink $link, $response): void
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
            $link->error($this->errorFor($response));
        }

        $link->checkedAt(now());
        $link->nextCheckAt(now()->add($this->frequencyInterval()));

        if ($ok) {
            $link->brokenSince(null)->notifiedAt(null);
        } elseif (! $link->brokenSince()) {
            $link->brokenSince(now());
        }

        $link->save();
    }

    private function errorFor($response): string
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

    private function frequencyInterval(): CarbonInterval
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
}
