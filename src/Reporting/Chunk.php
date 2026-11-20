<?php

namespace Statamic\SeoPro\Reporting;

use Illuminate\Support\Facades\Cache;
use Statamic\Facades\Data;
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

    public function id()
    {
        return $this->id;
    }

    public function contentIds()
    {
        return $this->contentIds;
    }

    public function report()
    {
        return $this->report;
    }

    public function save()
    {
        $this->repository()->saveChunk($this);

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

        $this->repository()->deleteChunk($this);

        if ($this->wasLastChunk()) {
            $this->report->finalize();
        }
    }

    protected function wasLastChunk()
    {
        return ! $this->repository()->hasChunks($this->report);
    }

    protected function repository()
    {
        return app(ReportRepository::class);
    }

    protected function createPage($content)
    {
        if ($content->value('seo') === false || is_null($content->uri())) {
            return;
        }

        $data = (new Cascade)
            ->withSiteDefaults(SiteDefaults::in($content->locale())->augmented())
            ->withSectionDefaults($this->getAugmentedSectionDefaults($content))
            ->with($content->augmentedValue('seo')->value())
            ->withCurrent($content)
            ->get();

        $data['site'] = $content->locale();

        return new Page($content->id(), $data, $this->report);
    }
}
