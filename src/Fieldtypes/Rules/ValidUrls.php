<?php

namespace Statamic\SeoPro\Fieldtypes\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ValidUrls implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hasInvalidUrl = collect($value)
            ->filter()
            ->contains(fn ($url) => ! Str::isUrl($url, ['http', 'https']));

        if ($hasInvalidUrl) {
            $fail('seo-pro::validation.urls')->translate();
        }
    }
}
