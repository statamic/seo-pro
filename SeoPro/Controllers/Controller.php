<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\CP\Publish\ProcessesFields;
use Statamic\CP\Publish\PreloadsSuggestions;

abstract class Controller extends \Statamic\Extend\Controller
{
    use ProcessesFields;
    use PreloadsSuggestions {
        getSuggestFields as protected getSuggestFieldsFromTrait;
    }

    protected function getSuggestFields($fields, $prefix = '')
    {
        $suggestFields = $this->getSuggestFieldsFromTrait($fields, $prefix);

        foreach ($fields as $handle => $config) {
            $type = array_get($config, 'type', 'text');

            if ($type === 'seo_pro.source') {
                $suggestFields['seo_pro'] = [
                    'type' => 'suggest',
                    'mode' => 'seo_pro',
                    'create' => true
                ];
            }
        }

        return $suggestFields;
    }
}
