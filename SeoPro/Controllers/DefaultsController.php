<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Fieldset;
use Illuminate\Http\Request;
use Statamic\Extend\Controller;
use Statamic\Addons\SeoPro\Settings;
use Statamic\CP\Publish\ProcessesFields;
use Statamic\CP\Publish\ValidationBuilder;
use Statamic\CP\Publish\PreloadsSuggestions;

class DefaultsController extends Controller
{
    use ProcessesFields;
    use PreloadsSuggestions {
        getSuggestFields as protected getSuggestFieldsFromTrait;
    }

    public function edit()
    {
        $fieldset = $this->fieldset();

        $data = $this->preProcessWithBlankFields(
            $fieldset,
            Settings::load()->get('defaults')
        );

        return $this->view('defaults', [
            'title' => 'SEO Defaults',
            'data' => $data,
            'fieldset' => $fieldset->toPublishArray(),
            'suggestions' => $this->getSuggestions($fieldset),
        ]);
    }

    public function update(Request $request)
    {
        $data = $this->processFields($this->fieldset(), $request->fields);

        Settings::load()->put('defaults', $data)->save();

        return ['success' => true, 'message' => trans('cp.saved_success')];
    }

    protected function fieldset()
    {
        return Fieldset::create('default',
            YAML::parse(File::get($this->getDirectory().'/fieldsets/defaults.yaml'))
        );
    }

    protected function getSuggestFields($fields, $prefix = '')
    {
        $suggestFields = $this->getSuggestFieldsFromTrait($fields, $prefix);

        foreach ($fields as $handle => $config) {
            $type = array_get($config, 'type', 'text');

            if ($type === 'seo_pro.source') {
                $suggestFields['seo_pro'] = [
                    'type' => 'suggest',
                    'mode' => 'seo_pro',
                    'create' => true
                ];
            }
        }

        return $suggestFields;
    }
}
