<?php

namespace Statamic\SeoPro\Reporting;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Artisan;
use Statamic\Facades\Entry;
use Statamic\Facades\File;
use Statamic\Facades\Folder;
use Statamic\Facades\Term;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\SiteDefaults;

class Report implements Arrayable, Jsonable
{
    use GetsSectionDefaults;

    protected $id;
    protected $pages;
    protected $results;
    protected $generatePages = false;
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

        $report->setId($id ?: static::nextId());

        return $report;
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
        return $this->id;
    }

    public function date()
    {
        return Carbon::parse($this->date);
    }

    public function generate()
    {
        $this->pages()->each(function ($page) {
            $page->validate();
        });

        $this->validateSite()->save();

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

    public function pages()
    {
        if ($this->pages) {
            return $this->pages;
        }

        return $this->createPages();
    }

    protected function createPages()
    {
        return $this->pages = $this->pagesFromContent();
    }

    protected function pagesFromContent()
    {
        return $this->allContent()
            ->map(function ($content) {
                if ($content->value('seo') === false || is_null($content->uri())) {
                    return;
                }

                $data = (new Cascade)
                    ->with(SiteDefaults::load()->augmented())
                    ->with($this->getAugmentedSectionDefaults($content))
                    ->with($content->augmentedValue('seo'))
                    ->withCurrent($content)
                    ->get();

                return (new Page)
                    ->setId($content->id())
                    ->setData($data)
                    ->setReport($this);
            })
            ->filter();
    }

    public function loadPages()
    {
        $dir = storage_path(sprintf('statamic/seopro/reports/%s/pages', $this->id));
        $files = Folder::getFilesRecursively($dir);

        $this->pages = collect($files)->map(function ($file) {
            $yaml = YAML::parse(File::get($file));

            return (new Page)
                ->setId($yaml['id'])
                ->setData($yaml['data'])
                ->setResults($yaml['results'])
                ->setReport($this);
        });

        return $this;
    }

    public function results()
    {
        return $this->results;
    }

    public function toArray()
    {
        $array = [
            'id' => $this->id,
            'date' => $this->date()->timestamp,
            'status' => $this->status(),
            'score' => $this->score(),
            'results' => $this->resultsToArray(),
        ];

        if ($this->generatePages) {
            $array['pages'] = $this->pages()->map(function ($page) {
                return [
                    'status' => $page->status(),
                    'url' => $page->url(),
                    'id' => $page->id(),
                    'edit_url' => $page->editUrl(),
                    'results' => $page->getRuleResults(),
                ];
            });
        }

        return $array;
    }

    protected function resultsToArray()
    {
        if (! $results = $this->results()) {
            return [];
        }

        $array = [];

        foreach ($this->results() as $class => $result) {
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

    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

    public function withPages()
    {
        $this->generatePages = true;

        return $this;
    }

    public static function all()
    {
        $folders = collect(Folder::getFolders(static::preparePath()));

        if ($folders->isEmpty()) {
            return $folders;
        }

        return $folders
            ->map(function ($path) {
                return (int) pathinfo($path)['filename'];
            })
            ->map(function ($id) {
                return static::find($id);
            })
            ->filter()
            ->sortByDesc
            ->date()
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

    public function save()
    {
        File::put($this->path(), YAML::dump([
            'date' => time(),
            'results' => $this->results,
        ]));

        return $this;
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
        $raw = YAML::parse(File::get($this->path()));

        $this->date = $raw['date'];
        $this->results = $raw['results'];
        $this->loadPages();

        return $this;
    }

    public static function queue()
    {
        $report = static::create()->save();

        $id = $report->id();

        Artisan::queue('statamic:seo-pro:generate-report', ['--report' => $id]);

        return $id;
    }

    public function status()
    {
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

    public function defaults()
    {
        return collect((new Cascade)
            ->with(SiteDefaults::load()->all())
            ->get());
    }

    public function score()
    {
        if ($this->score) {
            return $this->score;
        }

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

        return $this->score = round($score);
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
        return collect()
            ->merge(Entry::all())
            ->merge(Term::all())
            ->values();
    }
}
