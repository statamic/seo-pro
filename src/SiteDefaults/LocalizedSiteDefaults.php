<?php

namespace Statamic\SeoPro\SiteDefaults;

use Illuminate\Support\Collection;

class LocalizedSiteDefaults
{
    public function __construct(private string $locale, private Collection $defaults) {}

    public function locale(): string
    {
        return $this->locale;
    }

    public function all(): array
    {
        return $this->defaults->all();
    }

    public function set(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->defaults = collect($key);

            return $this;
        }

        $this->defaults->put($key, $value);

        return $this;
    }

    public function save(): bool
    {
        $save = SiteDefaults::newSave($this);

        // todo: dispatch event

        return $save;
    }

    public function origin(): ?LocalizedSiteDefaults
    {
        $origin = SiteDefaults::origins()->get($this->locale);

        if (! $origin) {
            return null;
        }

        return SiteDefaults::in($origin);
    }
}
