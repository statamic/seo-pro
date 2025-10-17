<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\CP\PublishForm;
use Statamic\Facades\Blueprint;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Fields;
use Statamic\Support\Arr;

abstract class BaseSectionDefaultsController extends CpController
{
    protected static $sectionType;

    abstract protected function getSectionItem($handle);

    public function edit($handle)
    {
        $this->authorize('edit seo section defaults');

        $sectionType = static::$sectionType;

        $item = $this->getSectionItem($handle);

        $seo = Arr::get($item->fileData(), 'inject.seo', []);

        if ($seo === false) {
            $seo = ['enabled' => false];
        }

        return PublishForm::make($this->blueprint())
            ->asConfig()
            ->icon('folder')
            ->title($item->title().' SEO')
            ->values($seo)
            ->submittingTo(cp_route("seo-pro.section-defaults.{$sectionType}.update", $item));
    }

    public function update($handle, Request $request)
    {
        $this->authorize('edit seo section defaults');

        $values = PublishForm::make($this->blueprint())->submit($request->all());

        $this->saveSectionItem(
            item: $this->getSectionItem($handle),
            values: Arr::removeNullValues($values)
        );
    }

    protected function blueprint()
    {
        return Blueprint::make()->setContents([
            'tabs' => [
                'main' => [
                    'sections' => Fields::new()->getConfig(),
                ],
            ],
        ]);
    }

    protected function saveSectionItem($item, $values)
    {
        $values = collect($values);

        $cascade = $item->cascade();

        if ($disabled = $values->get('enabled') === false) {
            $cascade->put('seo', false);
        } elseif ($values->except('enabled')->isEmpty()) {
            $cascade->forget('seo');
        } else {
            $cascade->put('seo', $values->except('enabled')->all());
        }

        $item->cascade($cascade->all())->save();

        if ($disabled) {
            $this->removeChildSeo($item);
        }
    }

    protected function removeChildSeo($item)
    {
        if ($item instanceof Collection) {
            $this->removeChildEntrySeo($item);
        } elseif ($item instanceof Taxonomy) {
            $this->removeChildTermSeo($item);
        } else {
            return;
        }
    }

    protected function removeChildEntrySeo($collection)
    {
        $collection->queryEntries()->get()->filter->has('seo')->each(function ($entry) {
            $entry->remove('seo')->save();
        });
    }

    protected function removeChildTermSeo($taxonomy)
    {
        $taxonomy->queryTerms()->get()->filter->has('seo')->each(function ($term) {
            $term->data($term->data()->except('seo'))->save();
        });
    }
}
