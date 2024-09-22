<?php

namespace Statamic\SeoPro\Content\Mappers\Concerns;

trait GetsSets
{
    protected function getSets(): array
    {
        if (! array_key_exists('sets', $this->fieldConfig)) {
            return [];
        }

        $sets = [];

        foreach ($this->fieldConfig['sets'] as $setGroup => $config) {
            if (! array_key_exists('sets', $config)) {
                continue;
            }

            foreach ($config['sets'] as $setName => $setConfig) {
                $sets[$setName] = $setConfig;
            }
        }

        return $sets;
    }
}
