<?php

namespace Statamic\SeoPro\Fieldtypes\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Statamic\Rules\Handle;

class SourceFieldRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Only valid handles should be allowed if 'from field' is selected as source
        if ($value['source'] === 'field') {
            (new Handle)->validate($attribute, $value['value'], $fail);
        }
    }
}
