<?php

namespace Statamic\Addons\SeoPro\Fieldtypes;

use Statamic\Extend\Fieldtype;
use Statamic\CP\FieldtypeFactory;

class FieldsFieldtype extends Fieldtype
{
    public $selectable = false;

    public function preProcess($config)
    {
        return collect($config)->map(function ($item) {
            return $this->preProcessField($item);
        })->all();
    }

    protected function preProcessField($config)
    {
        // Get the fieldtype for this field
        $type = $config['type'];
        $config_fieldtype = FieldtypeFactory::create($type);

        // Get the fieldtype's config fieldset
        $fieldset = $config_fieldtype->getConfigFieldset();

        // Pre-process all the fields in the fieldset
        foreach ($fieldset->fieldtypes() as $field) {
            // Ignore if the field isn't in the config
            if (! in_array($field->getName(), array_keys($config))) {
                continue;
            }

            $field->is_config = true;
            $config[$field->getName()] = $field->preProcess($config[$field->getName()]);
        }

        return $config;
    }
}
