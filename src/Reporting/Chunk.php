<?php

namespace Statamic\SeoPro\Reporting;

use Illuminate\Support\Facades\Cache;
use Statamic\Facades\Data;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

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
        $ids = $this->contentIds;

        dispatch(function () use ($ids) {
            $this->generate($ids);
        });
    }

    protected function generate($ids)
    {
        $content = Cache::get($this->report->cacheKey(Report::CONTENT_CACHE_KEY_SUFFIX));

        foreach ($ids as $id) {
            $this->createPage($content[$id] ?? Data::find($id))?->save();
        }

        File::delete($this->folderPath());

        if ($this->wasLastChunk()) {
            $this->report->finalize();
        }
    }

    protected function wasLastChunk()
    {
        return File::getFolders($this->report->chunksFolder())->isEmpty();
    }

    protected function createPage($content)
    {
        if ($content->value('seo') === false || is_null($content->uri())) {
            return;
        }

        $data = (new Cascade)
            ->with(SiteDefaults::in($content->locale())->augmented())
            ->with($this->getAugmentedSectionDefaults($content))
            ->with($content->augmentedValue('seo')->value())
            ->withCurrent($content)
            ->get();

        return new Page($content->id(), $data, $this->report);
    }
}
