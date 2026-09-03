<?php

namespace Statamic\SeoPro\Actions;

use Statamic\Actions\Delete;
use Statamic\SeoPro\Redirects\Redirect;

class DeleteRedirect extends Delete
{
    public function visibleTo($item): bool
    {
        return $item instanceof Redirect;
    }
}
