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
use Log;
class RobotsController extends Controller
{
    use TranslatesFieldsets;

    public function edit()
    {
        $fieldset = $this->fieldset();
        Log::info($fieldset);
        return $this->view('edit', [
            'title' => 'Robots.txt',
            'data' => Settings::load()->get('robots'),
            'fieldset' => $fieldset->toPublishArray(),
            'suggestions' => [],
            'submitUrl' => route('seopro.robots.update'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $this->processFields($this->fieldset(), $request->fields);

        Settings::load()->put('robots', $data)->save();

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
            'robots',
            YAML::parse(File::get($this->getDirectory().'/resources/fieldsets/robots.yaml'))
        ));
    }

    protected function write($content)
    {
        $data = (new TagData)
            ->with(Settings::load()->get('defaults'))
            ->get();

        $parsed = Parse::template($content, $data);

        File::disk('webroot')->put('robots.txt', $parsed);
    }

    protected function delete()
    {
        if (File::disk('webroot')->exists('robots.txt')) {
            File::disk('webroot')->delete('robots.txt');
        }
    }
}
