<?php

namespace Statamic\SeoPro\Tags;

use Statamic\Tags\Tags;
use Statamic\SeoPro\Cascade;

class SeoProTags extends Tags
{
    protected static $handle = 'seo_pro';

    /**
     * The {{ seo_pro:meta }} tag.
     *
     * @return string
     */
    public function meta()
    {
        if (array_get($this->context, 'seo') === false) {
            return;
        }

        return view('seo-pro::meta', $this->metaData());
    }

    /**
     * The {{ seo_pro:meta_data }} tag.
     *
     * @return string
     */
    public function metaData()
    {
        // $current = array_get($this->seo, 'page_object');
        $current = $this->context['seo']->augmentable();

        return (new Cascade)
            ->with(config('statamic.seo-pro.defaults'))
            // ->with($this->getSectionDefaults($current))
            ->with(array_get($this->context, 'seo', []))
            ->withCurrent($current)
            ->get();
    }

    /**
     * The {{ seo_pro:dump_meta_data }} tag.
     *
     * @return string
     */
    public function dumpMetaData()
    {
        return dd($this->metaData());
    }
}
