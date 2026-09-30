<?php

namespace Statamic\SeoPro\Actions;

use Statamic\Actions\Delete;
use Statamic\SeoPro\Redirects\Error;

class DeleteError extends Delete
{
    public function visibleTo($item): bool
    {
        return $item instanceof Error;
    }
}
