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

        $sections['seo'] = [
            'display' => 'SEO',
            'fields' => $fields
        ];

        $contents = $fieldset->contents();
        $contents['sections'] = $sections;
        $fieldset->contents($contents);
    }
}
