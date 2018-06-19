<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Parse;
use Statamic\API\Fieldset;
use Illuminate\Http\Request;
use Statamic\Addons\SeoPro\TagData;
use Statamic\Addons\SeoPro\Settings;

class HumansController extends Controller
{
    public function edit()
    {
        $fieldset = $this->fieldset();

        return $this->view('edit', [
            'title' => 'Humans.txt',
            'data' => $this->getConfig('humans'),
            'fieldset' => $fieldset->toPublishArray(),
            'suggestions' => [],
            'submitUrl' => route('seopro.humans.update'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $this->processFields($this->fieldset(), $request->fields);

        Settings::load()->put('humans', $data)->save();

        if ($data['enabled']) {
            $this->write($data['content']);
        } else {
            $this->delete();
        }

        return ['success' => true, 'message' => trans('cp.saved_success')];
    }

    protected function fieldset()
    {
        return Fieldset::create(
            'default',
            YAML::parse(File::get($this->getDirectory().'/resources/fieldsets/humans.yaml'))
        );
    }

    protected function write($content)
    {
        $data = (new TagData)
            ->with($this->getConfig('defaults'))
            ->get();

        $parsed = Parse::template($content, $data);

        File::disk('webroot')->put('humans.txt', $parsed);
    }

    protected function delete()
    {
        if (File::disk('webroot')->exists('humans.txt')) {
            File::disk('webroot')->delete('humans.txt');
        }
    }
}
