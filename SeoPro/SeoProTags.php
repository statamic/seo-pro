<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\File;
use Statamic\API\Parse;
use Statamic\Extend\Tags;

class SeoProTags extends Tags
{
    public function meta()
    {
        $data = array_merge(
            $this->getConfig('defaults'),
            array_get($this->context, 'seo', [])
        );

        return $this->render('meta', $data);
    }

    protected function render($template, $data = [])
    {
        $path = "{$this->getDirectory()}/resources/views/{$template}.html";

        $contents = File::get($path);

        return Parse::template($contents, $data, ['page' => array_get($this->context, 'page')]);
    }
}
