<?php

namespace Statamic\SeoPro\Llms;

use Illuminate\Contracts\Cache\Repository;
use Statamic\Sites\Site as SiteObject;

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

        if (is_array($cached)
            && ($cached['fingerprint'] ?? null) === $fingerprint
            && is_string($cached['contents'] ?? null)) {
            return $cached['contents'];
        }

        $contents = $this->renderer->render($document, $site);

        $this->cache->forever($this->key($site), [
            'fingerprint' => $fingerprint,
            'contents' => $contents,
        ]);

        return $contents;
    }

    public function forget(string|SiteObject|null $site = null): void
    {
        $this->cache->forget($this->key(Llms::site($site)));
    }

    private function key(SiteObject $site): string
    {
        return "seo-pro::llms.rendered.{$site->handle()}";
    }
}
