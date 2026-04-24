<?php

namespace Statamic\SeoPro\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Statamic\SeoPro\Facades\Redirect;

class UniqueRedirectUrl implements ValidationRule
{
    public function __construct(
        private $except = null,
        private ?string $site = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value) {
            return;
        }

        $existing = Redirect::query()
            ->where('source', $value)
            ->when($this->site, fn ($query) => $query->where('site', $this->site))
            ->first();

        if (! $existing || ($this->except && $this->except === $existing->id())) {
            return;
        }

        $fail(__('seo-pro::validation.unique_redirect_url'));
    }
}
