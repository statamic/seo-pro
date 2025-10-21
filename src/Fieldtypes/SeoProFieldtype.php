<?php

namespace Statamic\SeoPro\Fieldtypes;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blueprint;
use Statamic\Facades\GraphQL;
use Statamic\Fields\Fields as BlueprintFields;
use Statamic\Fields\Fieldtype;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\Fields as SeoProFields;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;
use Statamic\Statamic;
use Statamic\Support\Arr;

class SeoProFieldtype extends Fieldtype
{
    use GetsSectionDefaults;

    protected $selectable = true;
    protected $icon = 'seo-search-graph';

    public function preProcess($data)
    {
        if ($data === false) {
            $data = ['enabled' => false];
        }

        return $this->fields()->addValues($data ?? [])->preProcess()->values()->all();
    }

    public function preload()
    {
        return [
            'fields' => $this->fields()->toPublishArray(),
            'meta' => $this->fields()->addValues($this->field->value())->meta(),
        ];
    }

    public function process($data)
    {
        if (! Arr::get($data, 'enabled')) {
            return false;
        }

        $values = Arr::removeNullValues(
            $this->fields()->addValues($data)->process()->values()->all()
        );

        return Arr::except($values, 'enabled');
    }

    protected function fields()
    {
        // SeoProFields includes actual sections. However, fieldtypes can't span across multiple
        // sections, so we're mapping through the sections and adding the section fieldtype where necessary.
        $fields = collect($this->fieldConfig())
            ->map(function ($section) {
                return [
                    isset($section['display'])
                        ? ['handle' => Str::slug($section['display']), 'field' => ['type' => 'section',  'display' => $section['display'], 'instructions' => $section['instructions']]]
                        : null,
                    ...$section['fields'],
                ];
            })
            ->flatten(1)
            ->filter()
            ->values()
            ->all();

        return new BlueprintFields($fields);
    }

    protected function fieldConfig()
    {
        $parent = $this->field()->parent();

        if (! ($parent instanceof Entry || $parent instanceof Term)) {
            $parent = null;
        }

        return SeoProFields::new($parent ?? null)->getConfig();
    }

    public function extraRules(): array
    {
        $rules = $this
            ->fields()
            ->addValues((array) $this->field->value())
            ->validator()
            ->rules();

        return collect($rules)->mapWithKeys(function ($rules, $handle) {
            return [$this->field->handle().'.'.$handle => $rules];
        })->all();
    }

    public function augment($data)
    {
        if (Statamic::isApiRoute()) {
            $content = $this->field()->parent();

            return (new Cascade)
                ->with(SiteDefaults::in($this->field()->parent()->locale())->augmented())
                ->with($this->getAugmentedSectionDefaults($content))
                ->with($data)
                ->withCurrent($content)
                ->get();
        }

        if (empty($data) || ! is_iterable($data)) {
            return $data;
        }

        return Blueprint::make()
            ->setContents([
                'tabs' => [
                    'main' => [
                        'sections' => $this->fieldConfig(),
                    ],
                ],
            ])
            ->fields()
            ->addValues($data)
            ->augment()
            ->values()
            ->only(array_keys($data))
            ->all();
    }

    public function toGqlType()
    {
        return GraphQL::type('SeoPro');
    }
}
