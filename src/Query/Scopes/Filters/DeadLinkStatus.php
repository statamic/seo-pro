<?php

namespace Statamic\SeoPro\Query\Scopes\Filters;

use Statamic\Query\Scopes\Filter;
use Statamic\SeoPro\DeadLinks\Link;

use function Statamic\trans as __;

class DeadLinkStatus extends Filter
{
    // Not "status" - that's already used by Statamic's own entry status
    // filter, and filter handles are registered in a single shared list.
    protected static $handle = 'dead_link_status';

    public $pinned = true;

    public static function title()
    {
        return __('Status');
    }

    public function fieldItems()
    {
        return [
            'status' => [
                'type' => 'radio',
                'options' => $this->options(),
            ],
        ];
    }

    public function apply($query, $values)
    {
        $query->where('status', $values['status']);
    }

    public function badge($values)
    {
        return $this->options()[$values['status']] ?? null;
    }

    public function visibleTo($key)
    {
        return $key === 'dead-links';
    }

    protected function options()
    {
        return [
            Link::STATUS_FAILING => __('seo-pro::messages.failing'),
            Link::STATUS_OK => __('seo-pro::messages.ok'),
            Link::STATUS_PENDING => __('seo-pro::messages.pending'),
        ];
    }
}
