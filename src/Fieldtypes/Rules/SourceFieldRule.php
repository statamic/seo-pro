<?php

namespace Statamic\SeoPro\Fieldtypes\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Statamic\Fields\Field;
use Statamic\Rules\Handle;
use Statamic\Support\Arr;

class SourceFieldRule implements ValidationRule
{
    public function __construct(private ?Field $sourceField = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Only valid handles should be allowed if 'from field' is selected as the source.
        if ($value['source'] === 'field') {
            (new Handle)->validate($attribute, $value['value'], $fail);
        }

        // Run any validation rules that might belong on the source field.
        if ($value['source'] === 'custom') {
            $data = [];
            Arr::set($data, $attribute, $value['value']);

            Validator::make($data, [$attribute => $this->sourceField->get('validate', [])])->validate();
        }
    }
}
