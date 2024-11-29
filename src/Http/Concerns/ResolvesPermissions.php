<?php

namespace Statamic\SeoPro\Http\Concerns;

use Statamic\Facades\User;

trait ResolvesPermissions
{
    protected function getLinkPermissions(): array
    {
        return [
            'can_edit_link_sites' => User::current()->can('edit link sites'),
            'can_edit_link_collections' => User::current()->can('edit link collections'),
            'can_edit_global_links' => User::current()->can('edit global links'),
        ];
    }
}
