<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\File;
use Statamic\API\YAML;
use Statamic\Extend\Extensible;
use Statamic\Events\Data\AddonSettingsSaved;

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
        File::put($path = $this->path(), YAML::dump($data = $this->data->all()));

        event(new AddonSettingsSaved($path, $data));
    }

    protected function path()
    {
        return settings_path('addons/seo_pro.yaml');
    }
}
