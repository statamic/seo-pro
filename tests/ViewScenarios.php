<?php

namespace Tests;

trait ViewScenarios
{
    public function viewScenarioProvider()
    {
        return [
            ['antlers'],
            ['blade'],
            ['blade-components'],
        ];
    }

    protected function viewsPath()
    {
        return resource_path('views-seo-pro');
    }

    public function prepareViews($viewType)
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/views/'.$viewType, resource_path('views-seo-pro'));

        return $this;
    }

    public function cleanUpViews()
    {
        if ($this->files->exists($folder = resource_path('views-seo-pro'))) {
            $this->files->deleteDirectory($folder);
        }
    }
}
