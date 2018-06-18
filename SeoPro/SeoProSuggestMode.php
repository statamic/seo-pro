<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\Fieldset;
use Statamic\Addons\Suggest\Modes\AbstractMode;

class SeoProSuggestMode extends AbstractMode
{
    public function suggestions()
    {
        return collect(Fieldset::all())->flatMap(function ($fieldset) {
            $fieldsetName = $fieldset->name();
            $fieldsetTitle = $fieldset->title();

            return collect($fieldset->inlinedFields())->map(function ($config, $name) use ($fieldsetName, $fieldsetTitle) {
                return [
                    'value' => $fieldsetName . '/' . $name,
                    'text' => array_get($config, 'display', ucfirst($name)),
                    'optgroup' => $fieldsetTitle
                ];
            })->values()->all();
        })->all();
    }
}
