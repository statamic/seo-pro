<?php

namespace Statamic\Addons\SeoPro;

use ReflectionClass;
use Statamic\API\File;
use Statamic\API\Parse;
use Statamic\Extend\Tags;
use Statamic\Addons\SeoPro\Settings;

class SeoProTags extends Tags
{
    public function meta()
    {
        if (array_get($this->context, 'seo') === false) {
            return;
        }

        return $this->render('meta', $this->metaData());
    }

    public function metaData()
    {
        $current = array_get($this->context, 'page_object');
        $sectionDefaults = $this->getSectionDefaults($current);
        $currentValues = array_get($this->context, 'seo', []);

        return (new TagData)
            ->with(Settings::load()->get('defaults'))
            ->with(array_merge($sectionDefaults, $currentValues))
            ->withCurrent($current)
            ->get();
    }

    private function getSectionDefaults($obj)
    {
        // The cascadingData method is exactly what's needed here, but it's
        // protected. Rather than update core and need to require the
        // latest Statamic, just cheat with some reflection.
        $ref = new \ReflectionClass($obj);
        $met = $ref->getMethod('cascadingData');
        $met->setAccessible(true);
        $data = $met->invokeArgs($obj, []);
        return array_get($data, 'seo', []);
    }

    public function dumpMetaData()
    {
        return dd($this->metaData());
    }

    protected function render($template, $data = [])
    {
        $path = "{$this->getDirectory()}/resources/views/{$template}.html";

        $contents = File::get($path);

        return Parse::template($contents, $data);
    }
}
