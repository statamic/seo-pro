<?php

namespace Statamic\Addons\SeoPro\Events;

use Statamic\Data\Content\ContentCollection;

class CollectSitemapPagesEvent
{
    /**
     * @var \Statamic\Data\Content\ContentCollection
     */
    private $pages;

    public function __construct(ContentCollection $pages)
    {
        $this->pages = $pages;
    }

    /**
     * @return \Statamic\Data\Content\ContentCollection
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param \Statamic\Data\Content\ContentCollection $pages
     */
    public function setPages(ContentCollection $pages)
    {
        $this->pages = $pages;
    }
}
