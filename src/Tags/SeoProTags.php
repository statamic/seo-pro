<?php

namespace Statamic\SeoPro\Tags;

use Statamic\SeoPro\GetsOutputHTML;
use Statamic\Tags\Tags;

class SeoProTags extends Tags
{
    use GetsOutputHTML;

    protected static $handle = 'seo_pro';
}
