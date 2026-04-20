<?php

namespace Statamic\SeoPro\Redirects;

use Statamic\Fields\Blueprint;

interface RedirectRepository
{
    public function blueprint(): Blueprint;
}
