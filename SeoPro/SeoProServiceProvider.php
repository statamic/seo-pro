<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\File;
use Statamic\API\Parse;
use Statamic\Extend\ServiceProvider;

class SeoProServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $server = app(\League\Glide\Server::class);
        $server->setPresets($server->getPresets() + [
            'seo' => ['w' => 1200, 'h' => 1200, 'crop' => 'fit']
        ]);
    }
}
