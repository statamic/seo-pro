<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\File;
use Statamic\API\Parse;
use Statamic\Extend\Tags;

class SeoProTags extends Tags
{
    public function meta()
    {
        $data = (new TagData)
            ->with($this->getConfig('defaults'))
            ->with(array_get($this->context, 'seo', []))
            ->withCurrent(array_get($this->context, 'page', []))
            ->get();

        return $this->render('meta', $data);
    }

    protected function render($template, $data = [])
    {
        $path = "{$this->getDirectory()}/resources/views/{$template}.html";

        $contents = File::get($path);

        return Parse::template($contents, $data);
    }
}
