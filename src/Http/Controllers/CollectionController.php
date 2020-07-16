<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Fieldset;
use Illuminate\Http\Request;
use Statamic\API\Collection;
use Statamic\Addons\SeoPro\Settings;
use Statamic\Addons\SeoPro\TranslatesFieldsets;

class CollectionController extends Controller
{
    use TranslatesFieldsets;

    public function edit($collection)
    {
        $fieldset = $this->fieldset();
        $collection = Collection::whereHandle($collection);

        $data = $collection->get('seo');
        if ($data === false) {
            $data = ['enabled' => false];
        }

        $data = $this->preProcessWithBlankFields($fieldset, $data);

        return $this->view('edit', [
            'title' => $collection->title() . ' SEO',
            'data' => $data,
            'fieldset' => $fieldset->toPublishArray(),
            'suggestions' => $this->getSuggestions($fieldset),
            'submitUrl' => route('seopro.collections.update', ['collection' => $collection->path()]),
        ]);
    }

    public function update(Request $request, $handle)
    {
        if ($request->input('fields.enabled') === false) {
            $data = false;
        } else {
            $data = $this->processFields($this->fieldset(), array_except($request->fields, ['enabled']));
        }

        $collection = Collection::whereHandle($handle);

        if ($data === false) {
            $collection->set('seo', false);
        } elseif (empty($data)) {
            $collection->remove('seo');
        } else {
            $collection->set('seo', $data);
        }

        $collection->save();

        return [
            'success' => true,
            'message' => trans('cp.saved_success'),
            'redirect' => route('seopro.collections.edit', ['collection' => $handle]),
        ];
    }

    protected function fieldset()
    {
        return $this->translateFieldset(Fieldset::create(
            'sections',
            YAML::parse(File::get($this->getDirectory().'/resources/fieldsets/sections.yaml'))
        ));
    }
}
