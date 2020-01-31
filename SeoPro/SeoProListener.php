<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\Str;
use Statamic\API\Nav;
use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Collection;
use Statamic\Extend\Listener;
use Statamic\API\AssetContainer;
use Illuminate\Support\Facades\Cache;
use Statamic\Addons\SeoPro\Sitemap\Sitemap;

class SeoProListener extends Listener
{
    use GetsSectionDefaults;
    use TranslatesFieldsets;

    public $events = [
        'cp.nav.created' => 'addNavItems',
        \Statamic\Events\Data\PublishFieldsetFound::class => 'addFieldsetTab',
        \Statamic\Events\Data\FindingFieldset::class => 'addFieldsetTab',
        \Statamic\Events\RoutesMapping::class => 'addRoutes',
        'cp.add_to_head' => 'addToHead',
        \Statamic\Events\Data\CollectionSaved::class => 'clearSitemapCache',
        \Statamic\Events\Data\TaxonomySaved::class => 'clearSitemapCache',
        \Statamic\Events\Data\TermSaved::class => 'clearSitemapCache',
        \Statamic\Events\Data\EntrySaved::class => 'clearSitemapCache',
        \Statamic\Events\Data\PageSaved::class => 'clearSitemapCache',
    ];

    public function addNavItems($nav)
    {
        $seo = Nav::item('seo-pro')
            ->title('SEO Pro')
            ->route('seopro.dashboard')
            ->icon('magnifying-glass');

        $seo->add(function ($item) {
            $item->add(Nav::item('seo-pro-reports')
                ->title($this->trans('messages.reports'))
                ->route('seopro.reports.index'));
                
            $item->add(Nav::item('seo-pro-defaults')
                ->title($this->trans('messages.site_defaults'))
                ->route('seopro.defaults.edit'));

            $item->add(Nav::item('seo-pro-content')
                ->title($this->trans('messages.section_defaults'))
                ->route('seopro.sections.index'));

            $item->add(Nav::item('seo-pro-humans')
                ->title($this->trans('messages.humans_txt'))
                ->route('seopro.humans.edit'));

            $item->add(Nav::item('seo-pro-settings')
                ->title(trans_choice('cp.settings', 2))
                ->route('addon.settings', ['addon' => 'seo-pro']));
        });

        $nav->addTo('tools', $seo);
    }

    public function addFieldsetTab($event)
    {
        if (! in_array($event->type, ['page', 'entry', 'term'])) {
            return;
        }

        $fieldset = $event->fieldset;
        $sections = $fieldset->sections();

        $fields = YAML::parse(File::get($this->getDirectory().'/resources/fieldsets/content.yaml'))['fields'];

        $seoFields = collect($fields['seo']['fields'])->map(function ($field, $key) use ($event) {
            $field['placeholder'] = $this->getPlaceholder($key, $field, $event->data);
            return $field;
        })->all();

        $fields['seo']['fields'] = $this->translateFieldsetFields($seoFields, 'content');

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
        if (! $data) {
            return;
        }

        $vars = (new TagData)
            ->with(Settings::load()->get('defaults'))
            ->with($this->getSectionDefaults($data))
            ->with($data->get('seo', []))
            ->withCurrent($data)
            ->get();

        return array_get($vars, $key);
    }

    public function addRoutes($event)
    {
        if ($this->getConfig('sitemap_enabled') && site_locale() === default_locale()) {
            $event->router->get($this->getConfig('sitemap_url'), 'Statamic\Addons\SeoPro\Controllers\SitemapController@show');
        }
    }

    public function addToHead()
    {
        $assetContainer = $this->getConfig('asset_container', AssetContainer::all()->first()->handle());

        $suggestions = json_encode((new FieldSuggestions)->suggestions());

        return "<script>var SeoPro = { assetContainer: '{$assetContainer}', fieldSuggestions: {$suggestions} };</script>";
    }

    public function clearSitemapCache()
    {
        Cache::forget(Sitemap::CACHE_KEY);
    }
}
