<?php

namespace Statamic\SeoPro\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRedirectUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value) {
            return;
        }

        if (str_starts_with($value, '/')) {
            return;
        }

        $fail(__('seo-pro::validation.redirect_url'));
    }
}
