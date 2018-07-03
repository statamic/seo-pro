<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\API\Taxonomy;
use Statamic\API\Collection;

class ContentController extends Controller
{
    public function index()
    {
        return $this->view('content', [
            'collections' => Collection::all(),
            'taxonomies' => Taxonomy::all(),
        ]);
    }
}
