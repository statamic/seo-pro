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

class DefaultsController extends Controller
{
    use ProcessesFields;

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
            'fieldset' => $fieldset->toPublishArray()
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
}
