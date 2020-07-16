<?php

namespace Statamic\Addons\SeoPro;

trait GetsSectionDefaults
{
    private function getSectionDefaults($obj)
    {
        if (! method_exists($obj, 'cascadingData')) {
            return [];
        }

        // The cascadingData method is exactly what's needed here, but it's
        // protected. Rather than update core and need to require the
        // latest Statamic, just cheat with some reflection.
        $ref = new \ReflectionClass($obj);
        $met = $ref->getMethod('cascadingData');
        $met->setAccessible(true);
        $data = $met->invokeArgs($obj, []);
        return array_get($data, 'seo', []);
    }
}
