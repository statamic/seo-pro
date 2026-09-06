<?php

namespace Statamic\SeoPro\Tags;

use Illuminate\Support\Collection;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Query\Builder;
use Statamic\Facades\Image;
use Statamic\Facades\Site;
use Statamic\Fields\Value;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\RendersMetaHtml;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;
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
            ->withSiteDefaults(SiteDefaults::in($current?->locale() ?? Site::current()->handle())->augmented())
            ->withSectionDefaults($this->getAugmentedSectionDefaults($current))
            ->with($this->context->value('seo'))
            ->with($current ? [] : $this->context->except('template_content'))
            ->withCurrent($current)
            ->get();

        $metaData['is_twitter_glide_enabled'] = $this->isGlidePresetEnabled('seo_pro_twitter')
            && $this->shouldGlideSocialImage($metaData['image'] ?? null);
        $metaData['is_og_glide_enabled'] = $this->isGlidePresetEnabled('seo_pro_og')
            && $this->shouldGlideSocialImage($metaData['image'] ?? null);

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

    protected function shouldGlideSocialImage(mixed $image): bool
    {
        $asset = $this->socialImageAsset($image);

        if (! $asset) {
            return true;
        }

        return ! in_array(strtolower($asset->extension()), ['gif', 'svg'], true);
    }

    protected function socialImageAsset(mixed $image): ?Asset
    {
        if ($image instanceof Value) {
            $image = $image->value();
        }

        if ($image instanceof Collection || $image instanceof Builder) {
            $image = $image->first();
        }

        return $image instanceof Asset ? $image : null;
    }
}
