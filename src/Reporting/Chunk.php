<?php

namespace Statamic\SeoPro\Reporting;

use Statamic\Facades\Data;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\SiteDefaults;

class Chunk
{
    use GetsSectionDefaults;

    public $id;
    public $contentIds;
    public $report;

    public function __construct($id, $contentIds, Report $report)
    {
        $this->id = $id;
        $this->contentIds = $contentIds;
        $this->report = $report;
    }

    protected function folderPath()
    {
        return $this->report->chunksFolder()."/{$this->id}";
    }

    protected function yamlPath()
    {
        return $this->folderPath().'/chunk.yaml';
    }

    public function save()
    {
        File::put($this->yamlPath(), YAML::dump([
            'ids' => $this->contentIds,
        ]));

        return $this;
    }

    public function queueGenerate()
    {
        // TODO: Create queueable `generate-chunk` command/job.
        // Artisan::queue('statamic:seo-pro:generate-report', ['--report' => $this->id()]);

        return $this;
    }

    public function generate()
    {
        collect($this->contentIds)
            ->map(fn ($id) => $this->createPage(Data::find($id)))
            ->each(fn ($page) => $page->validate());

        File::delete($this->folderPath());
    }

    protected function createPage($content)
    {
        if ($content->value('seo') === false || is_null($content->uri())) {
            return;
        }

        $data = (new Cascade)
            ->with(SiteDefaults::load()->augmented())
            ->with($this->getAugmentedSectionDefaults($content))
            ->with($content->augmentedValue('seo')->value())
            ->withCurrent($content)
            ->get();

        return new Page($content->id(), $data, $this->report);
    }
}
