<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\Str;
use Statamic\API\Nav;
use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\Extend\Listener;

class SeoProListener extends Listener
{
    public $events = [
        'cp.nav.created' => 'addNavItems',
        \Statamic\Events\Data\FindingFieldset::class => 'addFieldsetTab',
    ];

    public function addNavItems($nav)
    {
        $nav->addTo('tools', Nav::item('seo-pro')
            ->title('SEO Pro')
            ->route('seopro.defaults.edit')
            ->icon('magnifying-glass'));
    }

    public function addFieldsetTab($event)
    {
        $fieldset = $event->fieldset;
        $sections = $fieldset->sections();

        $fields = YAML::parse(File::get($this->getDirectory().'/fieldsets/content.yaml'))['fields'];

        $fields['seo']['fields'] = collect($fields['seo']['fields'])->map(function ($field, $key) use ($event) {
            $field['placeholder'] = $this->getPlaceholder($key, $field, $event->data);
            return $field;
        })->all();

        $sections['seo'] = [
            'display' => 'SEO',
            'fields' => $fields
        ];

        $contents = $fieldset->contents();
        $contents['sections'] = $sections;
        $fieldset->contents($contents);
    }

    protected function getPlaceholder($key, $field, $data)
    {
        $vars = (new TagData)
            ->with($this->getConfig('defaults'))
            ->with($data->get('seo', []))
            ->withCurrent($data)
            ->get();

        return array_get($vars, $key);
    }
}
