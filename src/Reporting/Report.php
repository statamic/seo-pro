<?php

namespace Statamic\SeoPro\Reporting;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Statamic\CP\Column;
use Statamic\Facades\Entry;
use Statamic\Facades\File;
use Statamic\Facades\Folder;
use Statamic\Facades\Term;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\SiteDefaults;

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
    protected $results;
    protected $date;
    protected $score;

    public static $rules = [
        Rules\SiteName::class,
        Rules\UniqueTitleTag::class,
        Rules\UniqueMetaDescription::class,
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
        if (! $latest = static::latest()) {
            return 1;
        }

        return $latest->id() + 1;
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
        return File::exists($this->chunksFolder())
            && File::getFolders($this->chunksFolder())->isNotEmpty();
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
        if (File::exists($dir = $this->chunksFolder())) {
            File::delete($dir);
        }

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
            'results' => $this->resultsToArray(),
        ];

        if ($this->isGenerated() && $this->pages()) {
            $array['pages'] = $this->pagesToArray();
            $array['columns'] = [
                Column::make('status')->label(__('Status')),
                Column::make('page')->label(__('URL')),
                Column::make('actionable')->label(__('Actionable'))->sortable(false),
            ];

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

            $rule = (new $class)->setReport($this);

            $rule->load($result);

            $array[] = [
                'description' => $rule->description(),
                'status' => $rule->status(),
                'comment' => $rule->comment(),
            ];
        }

        return $array;
    }

    protected function pagesToArray()
    {
        return $this
            ->pages()
            ->map(fn ($page) => $this->pageToArray($page));
    }

    protected function pageToArray($page)
    {
        return [
            'status' => $page->status(),
            'url' => $page->url(),
            'id' => $page->id(),
            'edit_url' => $page->editUrl(),
            'results' => $page->getRuleResults(),
        ];
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

        $dir = storage_path(sprintf('statamic/seopro/reports/%s/pages', $this->id));

        $files = Folder::getFilesRecursively($dir);

        $this->pages = collect($files)
            ->map(fn ($file) => YAML::parse(File::get($file)))
            ->map(fn ($yaml) => (new Page($yaml['id'], $yaml['data'], $this))->setResults($yaml['results']))
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
        if ($this->isGenerating()) {
            return $this;
        } elseif ($this->isPending()) {
            return $this->queueGenerate();
        } elseif ($this->isLegacyReport()) {
            return $this->updateLegacyReport();
        }

        return $this->withPages(true);
    }

    public static function all()
    {
        $folders = collect(Folder::getFolders(static::preparePath()));

        if ($folders->isEmpty()) {
            return $folders;
        }

        return $folders
            ->map(fn ($path) => (int) pathinfo($path)['filename'])
            ->map(fn ($id) => static::find($id))
            ->filter()
            ->sortByDesc
            ->id()
            ->values();
    }

    public static function latest()
    {
        return static::all()->first();
    }

    public static function find($id)
    {
        $instance = static::create($id);

        if (! $instance->exists()) {
            return;
        }

        return $instance->load();
    }

    public function fresh()
    {
        return static::find($this->id());
    }

    public function save()
    {
        File::put($this->path(), YAML::dump($this->raw = [
            'date' => $this->date ?? now()->timestamp,
            'status' => $this->status(),
            'score' => $this->score(),
            'pages_crawled' => $this->pagesCrawled(),
            'results' => $this->results,
        ]));

        return $this;
    }

    public function delete()
    {
        File::delete($this->parentFolder());

        return $this->clearCaches();
    }

    public function parentFolder()
    {
        return storage_path('statamic/seopro/reports/'.$this->id);
    }

    public function chunksFolder()
    {
        return storage_path('statamic/seopro/reports/'.$this->id.'/chunks');
    }

    public function path()
    {
        return storage_path('statamic/seopro/reports/'.$this->id.'/report.yaml');
    }

    public function exists()
    {
        return File::exists($this->path());
    }

    public function load()
    {
        $this->raw = YAML::parse(File::get($this->path()));

        $this->date = $this->raw['date'];
        $this->results = $this->raw['results'];
        $this->score = $this->raw['score'] ?? null;
        $this->pagesCrawled = $this->raw['pages_crawled'] ?? null;

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

    public function defaults()
    {
        return collect((new Cascade)
            ->with(SiteDefaults::load()->all())
            ->get());
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

    public static function preparePath($path = null)
    {
        $path = collect([storage_path('statamic/seopro/reports'), $path])->filter()->implode('/');

        if (! File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        return $path;
    }

    protected function allContent()
    {
        $content = collect()
            ->merge(Entry::all())
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
}
