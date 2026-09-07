<?php

namespace Statamic\SeoPro\Widgets;

use Statamic\Facades\Scope;
use Statamic\Facades\User;
use Statamic\SeoPro\Facades\Error;
use Statamic\Widgets\VueComponent;
use Statamic\Widgets\Widget;

class RecentErrors extends Widget
{
    public function component()
    {
        if (! User::current()->can('view seo redirects')) {
            return;
        }

        $blueprint = Error::blueprint();

        $columns = $blueprint
            ->columns()
            ->setPreferred('seo-pro.errors.columns')
            ->rejectUnlisted()
            ->only(['url', 'last_hit_at'])
            ->values();

        return VueComponent::render('seo-pro-recent-errors-widget', [
            'columns' => $columns,
            'errorsUrl' => cp_route('seo-pro.errors.index'),
            'filters' => Scope::filters('errors'),
            'showTableHeader' => $this->config('show_table_header', false),
            'initialPerPage' => $this->config('limit', 5),
        ]);
    }
}
