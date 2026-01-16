<?php

namespace Statamic\SeoPro\Fieldtypes;

use Statamic\Fields\Field;
use Statamic\Fields\Fieldtype;
use Statamic\SeoPro\Fieldtypes\Rules\SourceFieldRule;
use Statamic\Support\Str;

class SourceFieldtype extends Fieldtype
{
    public static $handle = 'seo_pro_source';

    public $selectable = false;

    public function preProcess($data)
    {
        if (is_string($data) && Str::startsWith($data, '@seo:')) {
            return ['source' => 'field', 'value' => explode('@seo:', $data)[1]];
        }

        $originalData = $data;

        if ($data === false) {
            $data = null;
        }

        $data = $this->sourceField()
            ? $this->fieldtype()->preProcess($data)
            : $data;

        if ($originalData === false && $this->config('disableable') === true) {
            return ['source' => 'disable', 'value' => $data];
        }

        if (! $data && $this->config('inherit') !== false) {
            return ['source' => 'inherit', 'value' => $data];
        }

        // Handle issue with legacy `sitemap: true` section default.
        // This shouldn't ever be explicitly set `true` in Statamic v3,
        // but it may be migrated as `true` when coming from Statamic v2.
        if ($this->field->handle() === 'sitemap' && $originalData === true) {
            return ['source' => 'inherit', 'value' => null];
        }

        return ['source' => 'custom', 'value' => $data];
    }

    public function process($data)
    {
        if (!is_array($data) || !isset($data['source'])) {
            return null;
        }

        if ($data['source'] === 'field') {
            return $data['value'] ? '@seo:'.$data['value'] : null;
        }

        if ($data['source'] === 'inherit') {
            return null;
        }

        if ($data['source'] === 'disable') {
            return false;
        }

        return $this->fieldtype()->process($data['value']);
    }

    public function preload()
    {
        if (! $sourceField = $this->sourceField()) {
            return [
                'fieldMeta' => null,
                'defaultValue' => null,
            ];
        }

        $value = is_array($originalValue = $this->field->value())
            ? $originalValue['value']
            : $originalValue;

        return [
            'fieldMeta' => $sourceField->setValue($value)->preProcess()->meta(),
            'defaultFieldMeta' => $sourceField->setValue(null)->preProcess()->meta(),
            'defaultValue' => $sourceField->setValue(null)->preProcess()->value(),
        ];
    }

    public function augment($data)
    {
        if (is_string($data) && Str::startsWith($data, '@seo:')) {
            return $data;
        }

        if (! $this->sourceField()) {
            return $data;
        }

        $fieldtype = $this->fieldtype();

        if ($data === false) {
            $data = $fieldtype->defaultValue();
        }

        return $fieldtype->augment($data);
    }

    protected function sourceField()
    {
        if (! $config = $this->config('field')) {
            return;
        }

        return new Field(null, $config);
    }

    protected function fieldtype()
    {
        return $this->sourceField()->fieldtype();
    }

    public function rules(): array
    {
        return [new SourceFieldRule];
    }
}
