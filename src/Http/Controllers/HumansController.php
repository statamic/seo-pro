<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Parse;
use Statamic\API\Fieldset;
use Illuminate\Http\Request;
use Statamic\Addons\SeoPro\TagData;
use Statamic\Addons\SeoPro\Settings;
use Statamic\Addons\SeoPro\TranslatesFieldsets;

class HumansController extends Controller
{
    use TranslatesFieldsets;

    public function edit()
    {
        $fieldset = $this->fieldset();

        return $this->view('edit', [
            'title' => 'Humans.txt',
            'data' => Settings::load()->get('humans'),
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
        return $this->translateFieldset(Fieldset::create(
            'humans',
            YAML::parse(File::get($this->getDirectory().'/resources/fieldsets/humans.yaml'))
        ));
    }

    protected function write($content)
    {
        $data = (new TagData)
            ->with(Settings::load()->get('defaults'))
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
