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

        return $this->view('seo-pro::meta', $this->metaData());
    }

    /**
     * The {{ seo_pro:meta_data }} tag.
     *
     * @return string
     */
    public function metaData()
    {
        $current = optional($this->context->get('seo'))->augmentable();

        $metaData = (new Cascade)
            ->with(SiteDefaults::load()->augmented())
            ->with($this->getAugmentedSectionDefaults($current))
            ->with($this->context->get('seo'))
            ->withCurrent($current)
            ->get();

        $metaData['is_glide_enabled'] = (bool) config('statamic.seo-pro.assets.open_graph_preset');

        return $metaData;
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

    /**
     * Render normalized view partial.
     *
     * @param array $args
     * @return string
     */
    protected function view(...$args)
    {
        // Render view.
        $html = view(...$args)->render();

        // Remove new lines.
        $html = str_replace(["\n", "\r"], '', $html);

        // Remove whitespace between elements.
        $html = preg_replace('/(>)\s*(<)/', '$1$2', $html);

        // Add cleaner line breaks.
        $html = preg_replace('/(<[^\/])/', "\n$1", $html);

        return trim($html);
    }
}
