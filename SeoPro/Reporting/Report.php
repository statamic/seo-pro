<?php

namespace Statamic\Addons\SeoPro\Reporting;

use Statamic\API\Entry;
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

    protected $rules = [
        Rules\Site\UniqueTitleTag::class,
        Rules\Site\UniqueMetaDescription::class,
        Rules\Site\NoUnderscoresInUrl::class,
    ];

    public static function create()
    {
        return new static;
    }

    public function generate()
    {
        $this->pages()->each(function ($page) {
            $page->validate();
        });

        $this->validateSite();

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
    }

    public function pages()
    {
        if ($this->pages) {
            return $this->pages;
        }

        // For now, we're just dealing with entries. Eventually also pages, taxonomy
        // terms, and routes. Anything that can have a corresponding web page.
        $content = Entry::all();

        return $this->pages = $content->map(function ($content) {
            $data = (new TagData)
                ->with(Settings::load()->get('defaults'))
                ->with($content->getWithCascade('seo', []))
                ->withCurrent($content->toArray())
                ->get();

            return (new Page)->setData($data)->setReport($this);
        });
    }

    public function results()
    {
        return $this->results;
    }

    public function toArray()
    {
        $results = [];

        foreach ($this->results() as $class => $array) {
            $class = "Statamic\\Addons\\SeoPro\\Reporting\\Rules\\Site\\$class";
            $rule = new $class;
            $rule->setReport($this)->load($array);

            $results[] = [
                'description' => $rule->description(),
                'valid' => $rule->passes(),
                'comment' => $rule->comment(),
            ];
        }

        if ($this->generatePages) {
            $results = [
                'site' => $results,
                'pages' => $this->pages()->map(function ($page) {
                    return [
                        'valid' => $page->isValid(),
                        'url' => $page->url(),
                        'id' => $page->id(),
                        'results' => $page->getRuleResults()
                    ];
                })
            ];
        }

        return $results;
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
}
