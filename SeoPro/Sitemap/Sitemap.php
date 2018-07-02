<?php

namespace Statamic\Addons\SeoPro\Sitemap;

use Statamic\API\Content;
use Statamic\Addons\SeoPro\TagData;
use Statamic\Addons\SeoPro\Settings;

class Sitemap
{
    public function pages()
    {
        return Content::all()->map(function ($content) {
            $data = (new TagData)
                ->with(Settings::load()->get('defaults'))
                ->with($content->getWithCascade('seo', []))
                ->withCurrent($content->toArray())
                ->get();

            return (new Page)->with($data);
        })->sortBy(function ($page) {
            return substr_count(rtrim($page->path(), '/'), '/');
        });
    }
}
