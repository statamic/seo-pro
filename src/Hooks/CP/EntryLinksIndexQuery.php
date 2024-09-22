<?php

namespace Statamic\SeoPro\Hooks\CP;

use Statamic\Support\Traits\Hookable;

class EntryLinksIndexQuery
{
    use Hookable;

    public function __construct(
        private $query
    ) {}

    public function paginate(?int $perPage)
    {
        $payload = $this->runHooksWith('query', [
            'query' => $this->query,
        ]);

        return $payload->query->paginate($perPage);
    }
}
