<?php

namespace Statamic\SeoPro\Reporting;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;

class Report implements Arrayable, Jsonable
{
    const GENERATING_CACHE_KEY_SUFFIX = 'generating';
    const CONTENT_CACHE_KEY_SUFFIX = 'content';
    const PAGES_CACHE_KEY_SUFFIX = 'pages';
    const TO_ARRAY_CACHE_KEY_SUFFIX = 'to-array';

    protected $id;
    protected $raw;
    protected $pages;
    protected $pagesCrawled;
    protected $pagesActionable;
    protected $results;
    protected $date;
    protected $score;

    public static $rules = [
        Rules\SiteName::class,
        Rules\UniqueTitleTag::class,
        Rules\IdealTitleLength::class,
        Rules\UniqueMetaDescription::class,
        Rules\IdealMetaDescriptionLength::class,
        Rules\NoUnderscoresInUrl::class,
        Rules\ThreeSegmentUrls::class,
    ];

    public static function create($id = null)
    {
        $report = new static;

        return $report
            ->clearCaches()
            ->setId($id ?: static::nextId());
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public static function nextId()
    {
        return app(ReportRepository::class)->nextId();
    }

    public function id()
    {
        return (int) $this->id;
    }

    public function date()
    {
        return Carbon::parse($this->date);
    }

    public function queueGenerate()
    {
        if ($this->isGenerating()) {
            return $this;
        }

        $this->clearCaches();

        Cache::put($this->cacheKey(static::GENERATING_CACHE_KEY_SUFFIX), true);

        Artisan::queue('statamic:seo-pro:generate-report', ['--report' => $this->id()]);

        return $this;
    }

    protected function isPending()
    {
        return $this->status() === 'pending';
    }

    protected function isGenerating()
    {
        return Cache::has($this->cacheKey(static::GENERATING_CACHE_KEY_SUFFIX));
    }

    public function generate()
    {
        // The last chunk will trigger a `finalize()` call on our report,
        // which is important when chunks are being run asyncronously.
        $this
            ->chunks()
            ->each(fn ($chunk) => $chunk->queueGenerate());

        return $this;
    }

    protected function hasRemainingChunks()
    {
        return $this->repository()->hasChunks($this);
    }

    protected function chunks()
    {
        $chunks = $this
            ->allContent()
            ->chunk(config('statamic.seo-pro.reports.queue_chunk_size'))
            ->map(function ($chunk, $id) {
                return app()
                    ->makeWith(Chunk::class, [
                        'id' => $id + 1,
                        'contentIds' => $chunk->map->id()->values()->all(),
                        'report' => $this,
                    ])
                    ->save();
            });

        // Save to update report status now that we have chunks in storage
        $this->save();

        return $chunks;
    }

    public function finalize()
    {
        $this->repository()->deleteChunks($this);

        $this
            ->withPages()
            ->validatePages()
            ->validateSite();

        // Cache the pages for first load, since we already have them in memory here.
        Cache::put($this->cacheKey(static::PAGES_CACHE_KEY_SUFFIX), $this->pages());

        // Clear generating status before saving!
        Cache::forget($this->cacheKey(static::GENERATING_CACHE_KEY_SUFFIX));

        return $this->save();
    }

    protected function validatePages()
    {
        $this
            ->pages()
            ->each(fn ($page) => $page->validate());

        return $this;
    }

    protected function validateSite()
    {
        $results = [];

        foreach (static::$rules as $class) {
            $rule = new $class;

            $rule->setReport($this)->process();

            $results[$rule->id()] = $rule->save();
        }

        $this->results = $results;

        return $this;
    }

    public function pagesCrawled()
    {
        if ($this->pagesCrawled) {
            return $this->pagesCrawled;
        }

        return $this->pagesCrawled = $this->pages()?->count();
    }

    public function pagesActionable()
    {
        if ($this->pagesActionable) {
            return $this->pagesActionable;
        }

        return $this->pagesActionable = $this->pages()
            ?->filter(fn ($page) => in_array($page->status(), ['warning', 'fail']))
            ?->count();
    }

    public function results()
    {
        return $this->results;
    }

    public function toArray()
    {
        if ($this->isGenerated() && $array = Cache::get($this->cacheKey(static::TO_ARRAY_CACHE_KEY_SUFFIX))) {
            return $array;
        }

        $array = [
            'id' => $this->id(),
            'date' => $this->date()->timestamp,
            'status' => $this->status(),
            'score' => $this->score(),
            'pages_crawled' => $this->pagesCrawled(),
            'pages_actionable' => $this->pagesActionable(),
            'results' => $this->resultsToArray(),
        ];

        if ($this->isGenerated() && $this->pages() && $this->pages()->isNotEmpty()) {
            $array['pages'] = $this->pages()->map->toArray();

            Cache::put($this->cacheKey(static::TO_ARRAY_CACHE_KEY_SUFFIX), $array);
        }

        return $array;
    }

    protected function resultsToArray()
    {
        if (! $results = $this->results()) {
            return [];
        }

        $array = [];

        foreach ($results as $class => $result) {
            $class = "Statamic\\SeoPro\\Reporting\\Rules\\$class";

            if (! class_exists($class)) {
                continue;
            }

            $rule = (new $class)->setReport($this);

            $rule->load($result);

            $array[] = [
                'handle' => $rule->id(),
                'is_filterable' => $rule->validatesPages(),
                'description' => $rule->description(),
                'status' => $rule->status(),
                'comment' => $rule->comment(),
            ];
        }

        return $array;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    public function pages()
    {
        if ($this->isGenerated() && $pages = Cache::get($this->cacheKey(static::PAGES_CACHE_KEY_SUFFIX))) {
            return $pages;
        }

        return $this->pages;
    }

    public function withPages($preferCache = false)
    {
        if ($preferCache && $this->pages() && $this->pages()->isNotEmpty()) {
            return $this;
        }

        $this->pages = $this->repository()->pages($this)
            ->sortBy
            ->url()
            ->values();

        if ($this->isGenerated()) {
            Cache::put($this->cacheKey(static::PAGES_CACHE_KEY_SUFFIX), $this->pages);
        }

        return $this;
    }

    public function data()
    {
        return $this->withPages(true)->toArray();
    }

    public function generateIfNecessary(): self
    {
        if ($this->isGenerating()) {
            return $this;
        }

        if ($this->isPending()) {
            return $this->queueGenerate();
        }

        if ($this->isLegacyReport()) {
            return $this->updateLegacyReport();
        }

        return $this;
    }

    public static function all()
    {
        return app(ReportRepository::class)->all();
    }

    public static function latest()
    {
        return static::all()->first();
    }

    public static function latestGenerated()
    {
        return static::all()->filter(fn ($report) => $report->isGenerated())->first();
    }

    public static function find($id)
    {
        return app(ReportRepository::class)->find($id);
    }

    public function fresh()
    {
        return static::find($this->id());
    }

    public function save()
    {
        $this->raw = $this->raw();

        $this->repository()->save($this);

        static::deleteOldReports();

        return $this;
    }

    public function delete()
    {
        $this->repository()->delete($this);

        return $this->clearCaches();
    }

    public function raw()
    {
        return [
            'date' => $this->date ?? now()->timestamp,
            'status' => $this->status(),
            'score' => $this->score(),
            'pages_crawled' => $this->pagesCrawled(),
            'pages_actionable' => $this->pagesActionable(),
            'results' => $this->results,
        ];
    }

    public function hydrate(array $raw)
    {
        $this->raw = $raw;

        $this->date = $this->raw['date'];
        $this->results = $this->raw['results'];
        $this->score = $this->raw['score'] ?? null;
        $this->pagesCrawled = $this->raw['pages_crawled'] ?? null;
        $this->pagesActionable = $this->raw['pages_actionable'] ?? null;

        return $this;
    }

    public function status()
    {
        if ($this->isGenerating()) {
            return 'generating';
        }

        $results = $this->resultsToArray();

        if (empty($results)) {
            return 'pending';
        }

        $status = 'pass';

        foreach ($results as $result) {
            if ($result['status'] === 'warning') {
                $status = 'warning';
            }

            if ($result['status'] === 'fail') {
                return 'fail';
            }
        }

        return $status;
    }

    public function isGenerated()
    {
        return ! in_array($this->status(), ['pending', 'generating']);
    }

    public function score()
    {
        if (! $this->isGenerated()) {
            return null;
        }

        if ($this->isLegacyReport()) {
            return $this->score;
        }

        if ($this->score) {
            return $this->score;
        }

        $this->generateScore();

        return $this->score;
    }

    protected function generateScore()
    {
        $demerits = 0;
        $maxPoints = 0;

        if (! $this->results) {
            return 0;
        }

        foreach ($this->results as $class => $result) {
            $class = "Statamic\\SeoPro\\Reporting\\Rules\\$class";
            $rule = new $class;
            $rule->setReport($this)->load($result);

            $maxPoints += $rule->maxPoints();
            $demerits += $rule->demerits();
        }

        $score = ($maxPoints - $demerits) / $maxPoints * 100;

        $this->score = round($score);

        return $this;
    }

    protected function repository()
    {
        return app(ReportRepository::class);
    }

    protected function allContent()
    {
        $content = collect()
            ->merge(Entry::all()->whereNull('redirect'))
            ->merge(Term::all())
            ->keyBy
            ->id();

        // Cache this for use when generating chunks of pages in the queue by other processes.
        Cache::put($this->cacheKey(static::CONTENT_CACHE_KEY_SUFFIX), $content);

        return $content;
    }

    public function cacheKey($name)
    {
        return 'seo-pro-report-'.$this->id().'-'.$name;
    }

    public function clearCaches()
    {
        Cache::forget($this->cacheKey(static::GENERATING_CACHE_KEY_SUFFIX));
        Cache::forget($this->cacheKey(static::CONTENT_CACHE_KEY_SUFFIX));
        Cache::forget($this->cacheKey(static::PAGES_CACHE_KEY_SUFFIX));
        Cache::forget($this->cacheKey(static::TO_ARRAY_CACHE_KEY_SUFFIX));

        return $this;
    }

    public function isLegacyReport()
    {
        return $this->isGenerated() && ! array_key_exists('score', $this->raw ?? []);
    }

    public function updateLegacyReport()
    {
        return $this
            ->withPages(false)
            ->generateScore()
            ->save()
            ->fresh()
            ->withPages();
    }

    public static function deleteOldReports()
    {
        $keep = config('statamic.seo-pro.reports.keep_recent');

        if (! is_int($keep)) {
            return false;
        }

        static::all()->slice($keep)->each->delete();

        return true;
    }
}
