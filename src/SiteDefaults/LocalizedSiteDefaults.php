<?php

namespace Statamic\SeoPro\SiteDefaults;

use Illuminate\Support\Collection;
use Statamic\Data\HasOrigin;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Site;
use Statamic\SeoPro\Events\SiteDefaultsSaved;
use Statamic\SeoPro\Fields;

class LocalizedSiteDefaults
{
    use HasOrigin;

    public function __construct(private readonly string $locale, private Collection $defaults) {}

    public function id(): string
    {
        return "site_defaults::{$this->locale()}";
    }

    public function reference(): string
    {
        return $this->id();
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function site(): \Statamic\Sites\Site
    {
        return Site::get($this->locale());
    }

    public function defaults(): Collection
    {
        return $this->defaults;
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

    public function getOriginByString($origin)
    {
        return SiteDefaults::in($origin);
    }

    public function __get(string $name)
    {
        // The HasOrigin trait accesses the "data" property, but we call that "defaults".
        if ($name === 'data') {
            return $this->defaults;
        }
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

    public function editUrl(): string
    {
        return $this->cpUrl('seo-pro.site-defaults.edit');
    }

    public function updateUrl(): string
    {
        return $this->cpUrl('seo-pro.site-defaults.update');
    }

    private function cpUrl(string $route)
    {
        $params = [];

        if (Site::multiEnabled()) {
            $params['site'] = $this->locale();
        }

        return cp_route($route, $params);
    }
}
