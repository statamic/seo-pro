<?php

namespace Statamic\Addons\SeoPro;

trait TranslatesFieldsets
{
    protected function translateFieldset($fieldset)
    {
        $contents = $fieldset->contents();

        $contents['sections'] = collect($contents['sections'])->map(function ($section) use ($fieldset) {
            $section['fields'] = $this->translateFieldsetFields($section['fields'], $fieldset->name());
            return $section;
        })->all();

        $fieldset->contents($contents);

        return $fieldset;
    }

    protected function translateFieldsetFields($fields, $fieldset)
    {
        return collect($fields)->map(function ($field, $name) use ($fieldset) {
            $key = 'addons.SeoPro::fieldsets/'.$fieldset.'.'.$name;
            $field['display'] = $this->translateFieldsetKey($key);
            $field['instructions'] = $this->translateFieldsetKey($key.'_instruct');
            return $field;
        })->all();
    }

    private function translateFieldsetKey($key)
    {
        $trans = trans($key);

        if ($trans !== $key) {
            return $trans;
        }
    }
}
