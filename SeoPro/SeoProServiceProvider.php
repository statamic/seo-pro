<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\File;
use Statamic\API\Parse;
use Statamic\Extend\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Statamic\Addons\SeoPro\Middleware\Another;
use Statamic\Addons\SeoPro\Middleware\GenerateReport;

class SeoProServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->addGlidePresets();
        $this->registerMiddleware();
    }

    protected function addGlidePresets()
    {
        $server = app(\League\Glide\Server::class);
        $server->setPresets($server->getPresets() + [
            'seo' => ['w' => 1200, 'h' => 1200, 'crop' => 'fit']
        ]);
    }

    protected function registerMiddleware()
    {
        $this->app[Kernel::class]->pushMiddleware(GenerateReport::class);
    }
}
