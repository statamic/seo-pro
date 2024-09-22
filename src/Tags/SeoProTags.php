<?php

namespace Statamic\SeoPro\Tags;

use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Entry as EntryApi;
use Statamic\Facades\Site;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\RendersMetaHtml;
use Statamic\SeoPro\Reporting\Linking\ReportBuilder;
use Statamic\SeoPro\SeoPro;
use Statamic\SeoPro\SiteDefaults;
use Statamic\SeoPro\TextProcessing\Links\AutomaticLinkManager;
use Statamic\Structures\Page;
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

    protected function getAutoLinkedContent(string $content)
    {
        return app(AutomaticLinkManager::class)
            ->inject(
                $content,
                Site::current()?->handle() ?? 'default',
            );
    }

    public function content()
    {
        if (! SeoPro::isSeoProProcess()) {
            $content = $this->parse();

            if ($this->params->get('auto_link', false)) {
                return $this->getAutoLinkedContent($content);
            }

            return $content;
        }

        return '<!--statamic:content-->'.$this->parse().'<!--/statamic:content-->';
    }

    protected function makeRelatedContentReport(Entry $entry)
    {
        $related = app(ReportBuilder::class)
            ->getRelatedContentReport($entry, $this->params->get('limit', 10))
            ->getRelated(true);

        if ($as = $this->params->get('as')) {
            return [
                $as => $related,
            ];
        }

        return $related;
    }

    public function relatedContent()
    {
        $id = $this->params->get('for', $this->context->get('page.id'));

        if (! $id) {
            return [];
        }

        if ($id instanceof Page) {
            $id = $id->entry();
        }

        if ($id instanceof Entry) {
            return $this->makeRelatedContentReport($id);
        }

        $entry = EntryApi::find($id);

        if (! $entry) {
            return [];
        }

        return $this->makeRelatedContentReport($entry);
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
