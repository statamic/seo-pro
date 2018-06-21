<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\Str;
use Statamic\API\Nav;
use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Collection;
use Statamic\Extend\Listener;

class SeoProListener extends Listener
{
    public $events = [
        'cp.nav.created' => 'addNavItems',
        \Statamic\Events\Data\FindingFieldset::class => 'addFieldsetTab',
    ];

    public function addNavItems($nav)
    {
        $seo = Nav::item('seo-pro')
            ->title('SEO Pro')
            ->route('seopro.dashboard')
            ->icon('magnifying-glass');

        $seo->add(function ($item) {
            $item->add(Nav::item('seo-pro-defaults')
                ->title('Defaults')
                ->route('seopro.defaults.edit'));

            $item->add(Nav::item('seo-pro-collections')
                ->title('Collections')
                ->route('seopro.collections.index'));

            $item->add(Nav::item('seo-pro-humans')
                ->title('Humans')
                ->route('seopro.humans.edit'));

            $item->add(Nav::item('seo-pro-report')
                ->title('Report')
                ->route('seopro.report.index'));
        });

        $nav->addTo('tools', $seo);
    }

    public function addFieldsetTab($event)
    {
        $fieldset = $event->fieldset;
        $sections = $fieldset->sections();

        $fields = YAML::parse(File::get($this->getDirectory().'/resources/fieldsets/content.yaml'))['fields'];

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
            ->with($data->getWithCascade('seo', []))
            ->withCurrent($data)
            ->get();

        return array_get($vars, $key);
    }
}
