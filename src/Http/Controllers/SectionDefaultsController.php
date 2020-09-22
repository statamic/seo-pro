<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blueprint;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Fields;
use Statamic\Support\Arr;

abstract class SectionDefaultsController extends CpController
{
    protected static $sectionType;

    abstract protected function getSectionItem($handle);

    public function edit($handle)
    {
        $sectionType = static::$sectionType;

        $item = $this->getSectionItem($handle);

        $seo = Arr::get($item->fileData(), 'inject.seo', []);

        if ($seo === false) {
            $seo = ['enabled' => false];
        }

        $blueprint = $this->blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues($seo)
            ->preProcess();

        return view('seo-pro::edit', [
            'breadcrumbTitle' => __('seo-pro::messages.section_defaults'),
            'breadcrumbUrl' => cp_route('seo-pro.section-defaults.index'),
            'title' => $item->title().' SEO',
            'action' => cp_route("seo-pro.section-defaults.{$sectionType}.update", $item),
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
        ]);
    }

    public function update($handle, Request $request)
    {
        $blueprint = $this->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = Arr::removeNullValues($fields->process()->values()->all());

        $item = $this->getSectionItem($handle);

        $this->saveSectionItem($item, $values);
    }

    protected function blueprint()
    {
        return Blueprint::make()->setContents([
            'fields' => Fields::new()->getConfig(),
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
