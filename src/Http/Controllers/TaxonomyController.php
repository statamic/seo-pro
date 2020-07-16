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

        $data = $taxonomy->get('seo');
        if ($data === false) {
            $data = ['enabled' => false];
        }

        $data = $this->preProcessWithBlankFields($fieldset, $data);

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
        if ($request->input('fields.enabled') === false) {
            $data = false;
        } else {
            $data = $this->processFields($this->fieldset(), array_except($request->fields, ['enabled']));
        }

        $taxonomy = Taxonomy::whereHandle($handle);

        if ($data === false) {
            $taxonomy->set('seo', false);
        } elseif (empty($data)) {
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
        $yaml = YAML::parse(File::get($this->getDirectory().'/resources/fieldsets/sections.yaml'));

        // These don't apply to taxonomies.
        unset($yaml['sections']['main']['fields']['show_future']);
        unset($yaml['sections']['main']['fields']['show_past']);

        return $this->translateFieldset(Fieldset::create('sections', $yaml));
    }
}
