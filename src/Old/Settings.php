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
        $this->data = $this->getInitialConfig();
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

    protected function getInitialConfig()
    {
        $config = collect($this->getConfig());

        // Statamic merges in defaults with any user-defined configs, but only does one
        // level deep. It doesn't merge nested arrays. We'll go through and manually
        // merge in any of our nested array defaults, like "defaults" and "humans".
        $defaultConfig = YAML::parse(File::get($this->getDirectory().'/default.yaml'));
        foreach ($defaultConfig as $key => $value) {
            if (is_array($value)) {
                $config[$key] = array_merge($value, $config[$key]);
            }
        }

        return $config;
    }
}
