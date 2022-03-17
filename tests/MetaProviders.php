<?php

namespace Tests;

use Illuminate\Support\Facades\Blade;
use Statamic\Facades\Antlers;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Support\Str;
use Statamic\View\Cascade;

trait MetaProviders
{
    function metaProvider()
    {
        return [
            ['antlersMeta'],
            ['bladeMeta'],
        ];
    }

    public function antlersMeta($uri = null)
    {
        $site = Site::current();
        $data = Data::findByUri(Str::ensureLeft($uri, '/'), $site->handle());
        $context = (new Cascade(request(), $site))->withContent($data)->hydrate()->toArray();

        return (string) Antlers::parse('{{ seo_pro:meta }}', $context);
    }

    public function bladeMeta($uri = null)
    {
        $site = Site::current();
        $data = Data::findByUri(Str::ensureLeft($uri, '/'), $site->handle());
        $context = (new Cascade(request(), $site))->withContent($data)->hydrate()->toArray();

        ob_start() and extract($context, EXTR_SKIP);

        eval('?>' . Blade::compileString('@seo_pro(\'meta\')'));

        return (string) ob_get_clean();
    }
}
