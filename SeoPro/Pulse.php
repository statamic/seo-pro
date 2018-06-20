<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\Entry;
use Statamic\Extend\Extensible;

class Pulse
{
    use Extensible;

    protected $content;

    public function summary()
    {
        $this->content = Entry::all()->flatMap(function ($content) {
            return collect($content->locales())->mapWithKeys(function ($locale) use ($content) {
                return [$locale . '::' . $content->id() => $content->in($locale)->get()];
            });
        });

        $values = collect();

        $data = $this->content->map(function ($entry) {
            return $this->getData($entry);
        });

        $data->each(function ($item, $id) use ($values) {
            foreach ($item as $key => $value) {
                // These are the fields we're interested in checking for.
                // The rest are ok to be duplicated.
                if (! in_array($key, ['title', 'description'])) {
                    continue;
                }

                $values[$key] = $values->get($key, collect());
                $values[$key][$id] = $value;
            }
        });

        $summaries = $data->map(function ($item, $id) use ($values) {
            $fields = collect($item)->map(function ($value, $key) use ($id, $values) {
                return [
                    'unique' => $values->has($key) ? !$values[$key]->except($id)->contains($value) : null
                ];
            });

            $unique = null === $fields->first(function ($key, $item) {
                return $item['unique'] === false;
            });

            return [
                'id' => $id,
                'title' => $this->content->get($id)->get('title'),
                'url' => $this->content->get($id)->url(),
                'unique' => $unique,
                'fields' => $fields->all()
            ];
        });

        return $summaries->values()->all();
    }

    protected function getData($content)
    {
        return (new TagData)
            ->with($this->getConfig('defaults'))
            ->with($content->getWithCascade('seo', []))
            ->withCurrent($content->toArray())
            ->get();
    }
}
