<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\Fieldset;

class FieldSuggestions
{
    protected $allowedTypes = [
        'text',
        'textarea',
        'markdown',
        'redactor',
    ];

    public function suggestions()
    {
        return collect(Fieldset::all())->flatMap(function ($fieldset) {
            return collect($fieldset->inlinedFields())->map(function ($config, $name) {
                $type = array_get($config, 'type', 'text');

                if (! in_array($type, $this->allowedTypes)) {
                    return null;
                }

                return $name;
            })->filter();
        })->sort()->map(function ($name) {
            return ['value' => $name, 'text' => $name];
        })->values()->all();
    }
}
