<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Fieldset;
use Illuminate\Http\Request;
use Statamic\API\Collection;
use Statamic\Addons\SeoPro\Settings;

class CollectionController extends Controller
{
    public function edit($collection)
    {
        $fieldset = $this->fieldset();
        $collection = Collection::whereHandle($collection);

        $data = $this->preProcessWithBlankFields(
            $fieldset,
            $collection->get('seo', [])
        );

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
        $data = $this->processFields($this->fieldset(), $request->fields);

        $collection = Collection::whereHandle($handle);

        if (empty($data)) {
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
        return Fieldset::create(
            'default',
            YAML::parse(File::get($this->getDirectory().'/resources/fieldsets/content-defaults.yaml'))
        );
    }
}
