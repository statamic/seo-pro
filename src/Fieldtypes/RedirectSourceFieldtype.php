<?php

namespace Statamic\SeoPro\Fieldtypes;

use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\Support\Str;

class RedirectSourceFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $site = $this->resolveRedirectSite();

        return [
            'site_url' => Str::removeRight($site->absoluteUrl(), '/'),
        ];
    }

    public function process($data)
    {
        if (! $data) {
            return $data;
        }

        $site = $this->resolveRedirectSite();
        $siteUrl = Str::removeRight($site->absoluteUrl(), '/');

        if (Str::startsWith($data, $siteUrl)) {
            $data = Str::removeLeft($data, $siteUrl);
        }

        $siteHost = parse_url($site->absoluteUrl(), PHP_URL_HOST);

        if ($siteHost && preg_match('#^https?://'.preg_quote($siteHost, '#').'(:\d+)?(/.*)?$#', $data, $matches)) {
            $data = $matches[2] ?? '/';

            $sitePrefix = rtrim(parse_url($site->url(), PHP_URL_PATH) ?? '', '/');

            if ($sitePrefix && Str::startsWith($data, $sitePrefix.'/')) {
                $data = Str::removeLeft($data, $sitePrefix);
            }
        }

        if (! Str::startsWith($data, '/')) {
            $data = '/'.$data;
        }

        return $data;
    }

    private function resolveRedirectSite()
    {
        $redirect = request()->route('redirect');

        if ($redirect instanceof Redirect) {
            return Site::get($redirect->site());
        }

        return Site::selected();
    }
}
