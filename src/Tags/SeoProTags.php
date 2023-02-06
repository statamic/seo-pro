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
        if ($this->context->value('seo') === false) {
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
            ->with($this->context->value('seo'))
            ->with($current ? [] : $this->context->except('template_content'))
            ->withCurrent($current)
            ->get();

        $metaData['is_twitter_glide_enabled'] = $this->isGlidePresetEnabled('seo_pro_twitter');
        $metaData['is_og_glide_enabled'] = $this->isGlidePresetEnabled('seo_pro_og');

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
     * @param  array  $args
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

    /**
     * Check if glide preset is enabled.
     *
     * @param  string  $preset
     * @return bool
     */
    protected function isGlidePresetEnabled($preset)
    {
        $server = app(\League\Glide\Server::class);

        return collect($server->getPresets())->has($preset);
    }
}
