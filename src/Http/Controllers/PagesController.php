<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Fieldset;
use Illuminate\Http\Request;
use Statamic\API\PageFolder;
use Statamic\Addons\SeoPro\TranslatesFieldsets;

class PagesController extends Controller
{
    use TranslatesFieldsets;

    public function edit()
    {
        $fieldset = $this->fieldset();

        $folder = $this->folder();

        $data = $folder->get('seo');
        if ($data === false) {
            $data = ['enabled' => false];
        }

        $data = $this->preProcessWithBlankFields($fieldset, $data);

        return $this->view('edit', [
            'title' => translate_choice('cp.pages', 2) . ' SEO',
            'data' => $data,
            'fieldset' => $fieldset->toPublishArray(),
            'suggestions' => $this->getSuggestions($fieldset),
            'submitUrl' => route('seopro.pages.update'),
        ]);
    }

    public function update(Request $request)
    {
        if ($request->input('fields.enabled') === false) {
            $data = false;
        } else {
            $data = $this->processFields($this->fieldset(), array_except($request->fields, ['enabled']));
        }

        $folder = $this->folder();

        if ($data === false) {
            $folder->set('seo', false);
        } elseif (empty($data)) {
            $folder->remove('seo');
        } else {
            $folder->set('seo', $data);
        }

        $folder->save();

        return [
            'success' => true,
            'message' => trans('cp.saved_success'),
            'redirect' => route('seopro.pages.edit'),
        ];
    }

    protected function fieldset()
    {
        return $this->translateFieldset(Fieldset::create(
            'sections',
            YAML::parse(File::get($this->getDirectory().'/resources/fieldsets/sections.yaml'))
        ));
    }

    protected function folder()
    {
        $folder = PageFolder::whereHandle('/') ?: PageFolder::create();

        // Workaround for a Statamic issue. If whereHandle returned an existing folder,
        // the path would have been the full path with pages/ and folder.yaml in it.
        // It saves to the wrong place. It should have just been a slash.
        $folder->path('/');

        return $folder;
    }
}
