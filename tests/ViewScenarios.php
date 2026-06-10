<?php

namespace Tests;

trait ViewScenarios
{
    public static function viewScenarioProvider()
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
        $this->cleanUpViews();

        $this->files->copyDirectory(__DIR__.'/Fixtures/views/'.$viewType, resource_path('views-seo-pro'));

        // Clear compiled view cache to ensure fresh compilation with correct component paths
        $compiledPath = config('view.compiled');
        if ($compiledPath && $this->files->isDirectory($compiledPath)) {
            foreach ($this->files->files($compiledPath) as $file) {
                $this->files->delete($file);
            }
        }

        return $this;
    }

    public function cleanUpViews()
    {
        if ($this->files->exists($folder = resource_path('views-seo-pro'))) {
            $this->files->deleteDirectory($folder);
        }
    }
}
