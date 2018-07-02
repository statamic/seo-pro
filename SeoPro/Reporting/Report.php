<?php

namespace Statamic\Addons\SeoPro\Reporting;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Entry;
use Statamic\API\Folder;
use Statamic\Addons\SeoPro\TagData;
use Statamic\Addons\SeoPro\Settings;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Report implements Arrayable, Jsonable
{
    protected $id;
    protected $pages;
    protected $results;
    protected $generatePages = false;
    protected $date;
    public static $reportsToGenerate = [];

    protected $rules = [
        Rules\Site\UniqueTitleTag::class,
        Rules\Site\UniqueMetaDescription::class,
        Rules\Site\NoUnderscoresInUrl::class,
        Rules\Site\ThreeSegmentUrls::class,
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
        return carbon($this->date);
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

        foreach ($this->rules as $class) {
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
        // For now, we're just dealing with entries. Eventually also pages, taxonomy
        // terms, and routes. Anything that can have a corresponding web page.
        $content = Entry::all();

        return $this->pages = $content->map(function ($content) {
            $id = $content->id();

            $data = (new TagData)
                ->with(Settings::load()->get('defaults'))
                ->with($content->getWithCascade('seo', []))
                ->withCurrent($content->toArray())
                ->get();

            return (new Page)->setId($id)->setData($data)->setReport($this);
        });
    }

    public function loadPages()
    {
        $dir = temp_path(sprintf('/seopro/reports/%s/pages', $this->id));
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
            'results' => $this->resultsToArray(),
        ];

        if ($this->generatePages) {
            $array['pages'] = $this->pages()->map(function ($page) {
                return [
                    'status' => $page->status(),
                    'url' => $page->url(),
                    'id' => $page->id(),
                    'results' => $page->getRuleResults()
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
            $class = "Statamic\\Addons\\SeoPro\\Reporting\\Rules\\Site\\$class";
            $rule = new $class;
            $rule->setReport($this)->load($result);

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
        $folders = collect(Folder::getFolders(temp_path('seopro/reports')));

        if ($folders->isEmpty()) {
            return $files;
        }

        return $folders->map(function ($path) {
            return (int) pathinfo($path)['filename'];
        })->sort()->reverse()->map(function ($id) {
            return static::find($id);
        });
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
            'results' => $this->results
        ]));

        return $this;
    }

    public function path()
    {
        return temp_path('seopro/reports/' . $this->id . '/report.yaml');
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

        static::$reportsToGenerate[] = $id;

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
}
