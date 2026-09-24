<?php

namespace Statamic\SeoPro\Llms;

use Illuminate\Contracts\Cache\Repository;
use Statamic\Sites\Site as SiteObject;
use Throwable;

class LlmsRenderCache
{
    public function __construct(
        private Repository $cache,
        private LlmsRenderer $renderer,
    ) {}

    public function get(LlmsDocument $document, string|SiteObject|null $site = null): string
    {
        $site = Llms::site($site);
        $fingerprint = hash('sha256', (string) json_encode([
            'document' => $document->all(),
            'context' => $this->renderer->contextFingerprint($site),
        ], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        $cached = $this->cache->get($this->key($site));

        $lastContents = is_array($cached) && is_string($cached['contents'] ?? null) ? $cached['contents'] : null;

        if ($lastContents !== null && ($cached['fingerprint'] ?? null) === $fingerprint) {
            return $lastContents;
        }

        if ($lastContents !== null && ($cached['failed'] ?? null) === $fingerprint) {
            return $lastContents;
        }

        try {
            $contents = $this->renderer->render($document, $site);
        } catch (Throwable $exception) {
            if ($lastContents === null) {
                throw $exception;
            }

            // Keep serving the last successful render, and don't retry until something changes.
            report($exception);
            $this->cache->forever($this->key($site), [
                'fingerprint' => null,
                'failed' => $fingerprint,
                'contents' => $lastContents,
            ]);

            return $lastContents;
        }

        $this->cache->forever($this->key($site), [
            'fingerprint' => $fingerprint,
            'contents' => $contents,
        ]);

        return $contents;
    }

    public function forget(string|SiteObject|null $site = null): void
    {
        $key = $this->key(Llms::site($site));
        $cached = $this->cache->get($key);

        if (! is_array($cached) || ! is_string($cached['contents'] ?? null)) {
            $this->cache->forget($key);

            return;
        }

        // Keep the contents as a fallback in case the next render fails.
        $this->cache->forever($key, [
            'fingerprint' => null,
            'failed' => null,
            'contents' => $cached['contents'],
        ]);
    }

    private function key(SiteObject $site): string
    {
        return "seo-pro::llms.rendered.{$site->handle()}";
    }
}
