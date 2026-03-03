<?php

namespace Statamic\SeoPro\UpdateScripts;

use Statamic\Facades\Addon;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Support\Arr;
use Statamic\UpdateScripts\UpdateScript;

class MigrateSectionDefaultsToAddonSettings extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('7.2.0');
    }

    public function update()
    {
        $sectionDefaults = [];

        foreach (['collections' => Collection::all(), 'taxonomies' => Taxonomy::all()] as $type => $items) {
            foreach ($items as $item) {
                $injectSeo = Arr::get($item->fileData(), 'inject.seo');

                if ($injectSeo === null) {
                    continue;
                }

                $handle = $item->handle();

                if ($injectSeo === false) {
                    $sectionDefaults[$type][$handle] = false;
                } elseif (is_array($injectSeo) && ! empty($injectSeo)) {
                    if (Site::multiEnabled()) {
                        $sectionDefaults[$type][$handle]['sites'][Site::default()->handle()] = $injectSeo;
                    } else {
                        $sectionDefaults[$type][$handle] = $injectSeo;
                    }
                }

                // Remove inject.seo from the collection/taxonomy YAML.
                $cascade = $item->cascade();
                $cascade->forget('seo');
                $item->cascade($cascade->all())->save();
            }
        }

        if (! empty($sectionDefaults)) {
            $settings = Addon::get('statamic/seo-pro')->settings();
            $existing = $settings->get('section_defaults', []);
            $settings->set('section_defaults', array_merge_recursive($existing, $sectionDefaults))->save();
        }
    }
}
