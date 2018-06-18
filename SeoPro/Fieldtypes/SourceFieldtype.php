<?php

namespace Statamic\Addons\SeoPro\Fieldtypes;

use Statamic\API\Str;
use Statamic\Extend\Fieldtype;

class SourceFieldtype extends Fieldtype
{
    public function preProcess($data)
    {
        if (Str::startsWith($data, '@seo:')) {
            return ['source' => 'field', 'value' => explode('@seo:', $data)[1]];
        }

        if (! $data) {
            $data = '';
        }

        return ['source' => 'custom', 'value' => $data];
    }

    public function process($data)
    {
        if ($data['source'] === 'field') {
            return '@seo:' . $data['value'];
        }

        return $data['value'];
    }
}
