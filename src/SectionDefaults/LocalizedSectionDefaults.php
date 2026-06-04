<?php

namespace Statamic\SeoPro\SectionDefaults;

use Illuminate\Support\Collection;
use Statamic\Data\HasOrigin;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Site;
use Statamic\SeoPro\Events\SectionDefaultsSaved;
use Statamic\SeoPro\Fields;

class LocalizedSectionDefaults
{
    use HasOrigin;

    public function __construct(
        private readonly string $type,
        private readonly string $handle,
        private readonly string $locale,
        private Collection $defaults,
        private readonly bool $enabled = true,
    ) {}

    public function id(): string
    {
        return "section_defaults::{$this->type}::{$this->handle}::{$this->locale}";
    }

    public function reference(): string
    {
        return $this->id();
    }

    public function type(): string
    {
        return $this->type;
    }

    public function handle(): string
    {
        return $this->handle;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function site(): \Statamic\Sites\Site
    {
        return Site::get($this->locale());
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
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
        $save = SectionDefaults::save($this);

        SectionDefaultsSaved::dispatch($this);

        return $save;
    }

    public function origin(): ?LocalizedSectionDefaults
    {
        $origin = SectionDefaults::origins()->get($this->locale);

        if (! $origin) {
            return null;
        }

        return SectionDefaults::in($this->type, $this->handle, $origin);
    }

    public function getOriginByString($origin): ?LocalizedSectionDefaults
    {
        return SectionDefaults::in($this->type, $this->handle, $origin);
    }

    public function __get(string $name)
    {
        // The HasOrigin trait accesses the "data" property, but we call that "defaults".
        if ($name === 'data') {
            return $this->defaults;
        }
    }

    public function augmented(): array
    {
        $blueprint = Blueprint::make()
            ->setContents(['tabs' => ['main' => ['sections' => Fields::new()->getConfig()]]]);

        return $blueprint
            ->fields()
            ->addValues($this->values()->all())
            ->augment()
            ->values()
            ->only($this->keys()->all())
            ->all();
    }

    public function editUrl(): string
    {
        return $this->cpUrl('edit');
    }

    public function updateUrl(): string
    {
        return $this->cpUrl('update');
    }

    private function cpUrl(string $action): string
    {
        $params = [$this->handle];

        if (Site::multiEnabled()) {
            $params['site'] = $this->locale();
        }

        return cp_route("seo-pro.section-defaults.{$this->type}.{$action}", $params);
    }
}
