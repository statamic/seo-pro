<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Fieldset;
use Statamic\API\Taxonomy;
use Illuminate\Http\Request;
use Statamic\API\Collection;
use Statamic\Addons\SeoPro\Settings;

class TaxonomyController extends Controller
{
    public function edit($taxonomy)
    {
        $fieldset = $this->fieldset();
        $taxonomy = Taxonomy::whereHandle($taxonomy);

        $data = $this->preProcessWithBlankFields(
            $fieldset,
            $taxonomy->get('seo', [])
        );

        return $this->view('edit', [
            'title' => $taxonomy->title() . ' SEO',
            'data' => $data,
            'fieldset' => $fieldset->toPublishArray(),
            'suggestions' => $this->getSuggestions($fieldset),
            'submitUrl' => route('seopro.taxonomies.update', ['taxonomy' => $taxonomy->path()]),
        ]);
    }

    public function update(Request $request, $handle)
    {
        $data = $this->processFields($this->fieldset(), $request->fields);

        $taxonomy = Taxonomy::whereHandle($handle);

        if (empty($data)) {
            $taxonomy->remove('seo');
        } else {
            $taxonomy->set('seo', $data);
        }

        $taxonomy->save();

        return [
            'success' => true,
            'message' => trans('cp.saved_success'),
            'redirect' => route('seopro.taxonomies.edit', ['taxonomy' => $handle]),
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
