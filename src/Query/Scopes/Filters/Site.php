<?php

namespace Statamic\SeoPro\Query\Scopes\Filters;

use Statamic\Facades;
use Statamic\Query\Scopes\Filter;

use function Statamic\trans as __;

class Site extends Filter
{
    protected $pinned = true;

    public static function title()
    {
        return __('Site');
    }

    public function fieldItems()
    {
        $options = $this->options()->all();

        return [
            'site' => [
                'display' => __('Site'),
                'type' => count($options) > 5 ? 'select' : 'radio',
                'options' => $options,
            ],
        ];
    }

    public function autoApply()
    {
        return [
            'site' => Facades\Site::selected()->handle(),
        ];
    }

    public function apply($query, $values)
    {
        $query->where('site', $values['site']);
    }

    public function badge($values)
    {
        $site = Facades\Site::get($values['site']);

        return __('Site').': '.__($site->name());
    }

    public function visibleTo($key)
    {
        return in_array($key, ['redirects', 'errors']) && Facades\Site::hasMultiple();
    }

    protected function options()
    {
        return Facades\Site::authorized()
            ->mapWithKeys(fn ($site) => [$site->handle() => __($site->name())]);
    }
}
