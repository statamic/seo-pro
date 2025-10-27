<?php

namespace Statamic\SeoPro\Fieldtypes\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ValidJsonLd implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Str::startsWith($value['code'], '<script')) {
            $fail('seo-pro::validation.json_ld_omit_script_tags')->translate();
        }
    }
}
