<?php

namespace Statamic\SeoPro\Tags;

use Statamic\Facades\Image;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\RendersMetaHtml;
use Statamic\SeoPro\SiteDefaults;
use Statamic\Tags\Tags;

class SeoProTags extends Tags
{
    use GetsSectionDefaults,
        RendersMetaHtml;

    protected static $handle = 'seo_pro';

    /**
     * The {{ seo_pro:meta }} tag.
     *
     * @return string
     */
    public function meta()
    {
        if ($this->context->value('seo') === false) {
            return;
        }

        return $this->renderMetaHtml($this->metaData(), true);
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
            ->with($this->context->value('seo'))
            ->with($current ? [] : $this->context->except('template_content'))
            ->withCurrent($current)
            ->get();

        $metaData['is_twitter_glide_enabled'] = $this->isGlidePresetEnabled('seo_pro_twitter');
        $metaData['is_og_glide_enabled'] = $this->isGlidePresetEnabled('seo_pro_og');

        return $this->aliasedResult($metaData);
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
     * Check if glide preset is enabled.
     *
     * @param  string  $preset
     * @return bool
     */
    protected function isGlidePresetEnabled($preset)
    {
        return array_key_exists($preset, Image::customManipulationPresets());
    }
}
