<?php

namespace Statamic\SeoPro\Fieldtypes\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidOpeningHours implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hasHalfFilledDay = collect($value)
            ->filter(fn ($times) => is_array($times))
            ->contains(fn (array $times) => empty($times['opening']) !== empty($times['closing']));

        if ($hasHalfFilledDay) {
            $fail('seo-pro::validation.opening_hours_incomplete')->translate();
        }
    }
}
