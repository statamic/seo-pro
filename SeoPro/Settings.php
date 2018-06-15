<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\Extend\Extensible;

class Settings
{
    use Extensible;

    protected $data;

    public function __construct()
    {
        $this->data = collect($this->getConfig());
    }

    public static function load()
    {
        return new static;
    }

    public function get($key)
    {
        return $this->data->get($key);
    }

    public function put($key, $value)
    {
        $this->data->put($key, $value);

        return $this;
    }

    public function save()
    {
        File::put($this->path(), YAML::dump($this->data->all()));
    }

    protected function path()
    {
        return settings_path('addons/seo_pro.yaml');
    }
}
