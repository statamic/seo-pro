<?php

namespace Statamic\SeoPro\Tags;

use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\SiteDefaults;
use Statamic\Tags\Tags;

class SeoProTags extends Tags
{
    use GetsSectionDefaults;

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
        $current = optional($this->context->get('seo'))->augmentable();

        return (new Cascade)
            ->with(SiteDefaults::load()->augmented())
            ->with($this->getAugmentedSectionDefaults($current))
            ->with($this->context->get('seo'))
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
