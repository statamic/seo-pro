<?php

namespace Statamic\Addons\SeoPro\Reporting;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\API\Content;

class Page
{
    protected $report;
    protected $data;
    protected $results;
    protected $id;

    protected $rules = [
        Rules\Page\UniqueTitleTag::class,
        Rules\Page\UniqueMetaDescription::class,
        Rules\Page\NoUnderscoresInUrl::class,
        Rules\Page\ThreeSegmentUrls::class,
    ];

    public function setData($data)
    {
        $this->data = collect($data);

        return $this;
    }

    public function setResults($results)
    {
        $this->results = $results;

        return $this;
    }

    public function setReport(Report $report)
    {
        $this->report = $report;

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
        return $this->data->get($key);
    }

    public function status()
    {
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

        foreach ($this->results as $class => $array) {
            $class = "Statamic\\Addons\\SeoPro\\Reporting\\Rules\\$class";
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

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function save()
    {
        $data = [
            'id' => $this->id,
            'data' => $this->data->all(),
            'results' => $this->results
        ];

        File::put($this->path(), YAML::dump($data));
    }

    protected function path()
    {
        $key = md5($this->id);
        $parts = array_slice(str_split($key, 2), 0, 2);

        return temp_path(vsprintf('/seopro/reports/%s/pages/%s/%s.yaml', [
            $this->report->id(), implode('/', $parts), $key
        ]));
    }

    public function model()
    {
        return Content::find($this->id);
    }

    public function editUrl()
    {
        if (starts_with($this->id, 'route:')) {
            return route('settings.edit', ['settings' => 'routes']);
        }

        return $this->model()->editUrl();
    }
}
