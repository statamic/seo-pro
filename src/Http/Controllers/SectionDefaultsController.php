<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\Blueprint;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Fields;
use Statamic\SeoPro\SiteDefaults;
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

        $blueprint = $this->blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues($seo ?: ['enabled' => false])
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

        if ($values->get('enabled') === false) {
            $cascade->put('seo', false);
        } elseif ($values->except('enabled')->isEmpty()) {
            $cascade->forget('seo');
        } else {
            $cascade->put('seo', $values->except('enabled')->all());
        }

        $item->cascade($cascade->all())->save();
    }
}
