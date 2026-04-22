<?php

namespace Statamic\SeoPro\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Statamic\SeoPro\Facades\Redirect;

class UniqueRedirectUrl implements ValidationRule
{
    public function __construct(
        private ?string $except = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value) {
            return;
        }

        $existing = Redirect::query()->where('source', $value)->first();

        if (! $existing) {
            return;
        }

        if ($this->except && $this->except === $existing->id()) {
            return;
        }

        $fail(__('seo-pro::validation.unique_redirect_url'));
    }
}
