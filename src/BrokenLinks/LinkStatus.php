<?php

namespace Statamic\SeoPro\BrokenLinks;

use Statamic\Contracts\Query\QueryableValue;

enum LinkStatus: string implements QueryableValue
{
    case Pending = 'pending';
    case Ok = 'ok';
    case Broken = 'broken';

    public function toQueryableValue()
    {
        return $this->value;
    }
}
