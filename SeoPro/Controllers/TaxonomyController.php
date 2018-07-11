<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Fieldset;
use Statamic\API\Taxonomy;
use Illuminate\Http\Request;
use Statamic\Addons\SeoPro\Settings;
use Statamic\Addons\SeoPro\TranslatesFieldsets;

class TaxonomyController extends Controller
{
    use TranslatesFieldsets;

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
        return $this->translateFieldset(Fieldset::create(
            'content-defaults',
            YAML::parse(File::get($this->getDirectory().'/resources/fieldsets/content-defaults.yaml'))
        ));
    }
}
