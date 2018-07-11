<?php

namespace Statamic\Addons\SeoPro;

trait TranslatesFieldsets
{
    protected function translateFieldset($fieldset)
    {
        $contents = $fieldset->contents();

        $contents['sections'] = collect($contents['sections'])->map(function ($section) use ($fieldset) {
            $section['fields'] = collect($section['fields'])->map(function ($field, $name) use ($fieldset) {
                $key = 'addons.SeoPro::fieldsets/'.$fieldset->name().'.'.$name;
                $field['display'] = $this->translateFieldsetKey($key);
                $field['instructions'] = $this->translateFieldsetKey($key.'_instruct');
                return $field;
            })->all();
            return $section;
        });

        $fieldset->contents($contents);

        return $fieldset;
    }

    private function translateFieldsetKey($key)
    {
        $trans = trans($key);

        if ($trans !== $key) {
            return $trans;
        }
    }
}
