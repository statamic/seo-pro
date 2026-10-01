<?php

namespace Statamic\SeoPro\Query\Scopes\Filters;

use Statamic\Query\Scopes\Filter;
use Statamic\SeoPro\BrokenLinks\ExternalLink;

use function Statamic\trans as __;

class ExternalLinkStatus extends Filter
{
    protected static $handle = 'broken_link_status';

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
        return $key === 'broken-links';
    }

    protected function options()
    {
        return [
            ExternalLink::STATUS_FAILING => __('seo-pro::messages.failing'),
            ExternalLink::STATUS_OK => __('seo-pro::messages.ok'),
            ExternalLink::STATUS_PENDING => __('seo-pro::messages.pending'),
        ];
    }
}
