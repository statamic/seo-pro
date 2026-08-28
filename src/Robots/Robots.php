<?php

namespace Statamic\SeoPro\Robots;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Statamic\Facades\Addon;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Sites\Site as SiteObject;

class Robots
{
    public static function get(): RobotsPolicy
    {
        return Blink::once('seo-pro::robots', function () {
            $data = Arr::get(Addon::get('statamic/seo-pro')->settings()->raw(), 'robots', []);

            return new RobotsPolicy(self::policyData($data));
        });
    }

    public static function generated(): ?array
    {
        return Arr::get(Addon::get('statamic/seo-pro')->settings()->raw(), 'robots.generated');
    }

    public static function save(RobotsPolicy $policy): bool
    {
        $robots = ['policy' => $policy->all()];

        if ($generated = self::generated()) {
            $robots['generated'] = $generated;
        }

        return self::saveSettings($robots);
    }

    public static function saveGenerated(RobotsPolicy $policy, string $contents, Carbon $generatedAt): bool
    {
        return self::saveSettings([
            'policy' => $policy->all(),
            'generated' => [
                'timestamp' => $generatedAt->toIso8601String(),
                'checksum' => hash('sha256', $contents),
            ],
        ]);
    }

    public static function authority(SiteObject $site): string
    {
        $parts = parse_url(URL::makeAbsolute($site->url()));
        $scheme = strtolower($parts['scheme'] ?? 'http');
        $port = $parts['port'] ?? null;
        $port = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)
            ? null
            : $port;

        return $scheme.'://'.strtolower($parts['host'] ?? '').($port ? ':'.$port : '');
    }

    public static function withDefaults(array $data): array
    {
        $defaults = self::defaults();
        $data = Arr::only($data, array_keys($defaults));
        $values = array_replace($defaults, $data);
        $values['ai'] = array_replace($defaults['ai'], $data['ai'] ?? []);
        $values['content_signals'] = array_replace($defaults['content_signals'], $data['content_signals'] ?? []);
        $values['allow'] = is_array($values['allow']) ? $values['allow'] : $defaults['allow'];
        $values['disallow'] = is_array($values['disallow']) ? $values['disallow'] : $defaults['disallow'];
        $values['custom_source'] = (string) ($values['custom_source'] ?? '');
        $values['preset'] = $values['preset'] === 'open' ? 'full' : $values['preset'];

        return $values;
    }

    public static function defaults(): array
    {
        return [
            'mode' => 'managed',
            'preset' => 'neutral',
            'allow' => ['/'],
            'disallow' => [],
            'ai' => [
                'search' => 'neutral',
                'agent' => 'neutral',
                'training' => 'neutral',
            ],
            'content_signals' => [
                'search' => null,
                'ai_input' => null,
                'ai_train' => null,
                'use' => null,
            ],
            'include_sitemap' => true,
            'custom_source' => '',
        ];
    }

    private static function policyData(array $data): array
    {
        if (isset($data['policy']) && is_array($data['policy'])) {
            return $data['policy'];
        }

        if (array_intersect(array_keys(self::defaults()), array_keys($data))) {
            return $data;
        }

        $default = Site::default()?->handle();

        return is_array($data[$default] ?? null)
            ? $data[$default]
            : (collect($data)->first(fn ($value) => is_array($value)) ?? []);
    }

    private static function saveSettings(array $robots): bool
    {
        $saved = Addon::get('statamic/seo-pro')->settings()
            ->set('robots', $robots)
            ->save();

        if ($saved) {
            Blink::forget('seo-pro::robots');
        }

        return $saved;
    }
}
