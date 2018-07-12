<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\File;
use Statamic\API\Parse;
use Statamic\Extend\Tags;
use Statamic\Addons\SeoPro\Settings;

class SeoProTags extends Tags
{
    public function meta()
    {
        if (array_get($this->context, 'response_code', 200) === 404) {
            // Temporarily do nothing for 404 pages.
            return;
        }

        $data = (new TagData)
            ->with(Settings::load()->get('defaults'))
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
