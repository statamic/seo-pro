<?php

namespace Statamic\SeoPro\SiteDefaults;

use Illuminate\Support\Collection;
use Statamic\Facades\Blueprint;
use Statamic\SeoPro\Events\SiteDefaultsSaved;
use Statamic\SeoPro\Fields;

class LocalizedSiteDefaults
{
    public function __construct(private readonly string $locale, private Collection $defaults) {}

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
        $save = SiteDefaults::save($this);

        SiteDefaultsSaved::dispatch($this);

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

    public function blueprint(): \Statamic\Fields\Blueprint
    {
        return SiteDefaults::blueprint();
    }

    public function augmented(): array
    {
        $contentValues = Blueprint::make()
            ->setContents(['tabs' => ['main' => ['sections' => Fields::new()->getConfig()]]])
            ->fields()
            ->addValues($this->all())
            ->augment()
            ->values();

        $defaultValues = $this->blueprint()
            ->fields()
            ->addValues($this->all())
            ->augment()
            ->values();

        return $defaultValues
            ->merge($contentValues)
            ->only($this->defaults->keys()->all())
            ->all();
    }
}
