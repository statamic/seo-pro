<?php

namespace Statamic\SeoPro\Reporting;

use Statamic\Facades\Data;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Statamic\Support\Arr;

class Page
{
    protected $id;
    protected $data;
    protected $report;
    protected $results;

    protected $rules = [
        Rules\UniqueTitleTag::class,
        Rules\UniqueMetaDescription::class,
        Rules\NoUnderscoresInUrl::class,
        Rules\ThreeSegmentUrls::class,
    ];

    public function __construct($id, $data, Report $report)
    {
        $this->id = $id;
        $this->data = $data;
        $this->report = $report;
    }

    public function setResults($results)
    {
        $this->results = $results;

        return $this;
    }

    public function report()
    {
        return $this->report;
    }

    public function results()
    {
        return $this->results;
    }

    public function validate()
    {
        $results = [];

        foreach (Report::$rules as $class) {
            $rule = new $class;

            if (! $rule->validatesPages()) {
                continue;
            }

            $rule->setReport($this->report())->setPage($this)->process();
            $results[$rule->id()] = $rule->save();
        }

        $this->results = $results;

        $this->save();

        return $this;
    }

    public function get($key)
    {
        return Arr::get($this->data, $key);
    }

    public function status()
    {
        if (! $this->results) {
            return 'pending';
        }

        $status = 'pass';

        foreach ($this->getRuleResults() as $result) {
            if ($result['status'] === 'warning') {
                $status = 'warning';
            }

            if ($result['status'] === 'fail') {
                return 'fail';
            }
        }

        return $status;
    }

    public function getRuleResults()
    {
        $results = collect();

        if (! $this->results) {
            return $results;
        }

        foreach ($this->results as $class => $array) {
            $class = "Statamic\\SeoPro\\Reporting\\Rules\\$class";
            $rule = new $class;

            if (! $rule->validatesPages()) {
                continue;
            }

            $rule->setPage($this)->load($array);

            $results[] = [
                'description' => $rule->description(),
                'status' => $rule->status(),
                'comment' => $rule->comment(),
            ];
        }

        return $results;
    }

    public function url()
    {
        return $this->get('canonical_url');
    }

    public function id()
    {
        return $this->id;
    }

    public function save()
    {
        $data = [
            'id' => $this->id,
            'data' => $this->data,
            'results' => $this->results,
        ];

        File::put($this->path(), YAML::dump($data));
    }

    protected function path()
    {
        $key = md5($this->id);
        $parts = array_slice(str_split($key, 2), 0, 2);

        return storage_path(vsprintf('statamic/seopro/reports/%s/pages/%s/%s.yaml', [
            $this->report->id(), implode('/', $parts), $key,
        ]));
    }

    public function model()
    {
        return Data::find($this->id);
    }

    public function editUrl()
    {
        if (starts_with($this->id, 'route:')) {
            return route('settings.edit', ['settings' => 'routes']);
        }

        return $this->model()->editUrl();
    }
}
