<?php

namespace Statamic\SeoPro\DeadLinks;

use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;

interface LinkRepository
{
    public function all(): Collection;

    public function query(): LinkQueryBuilder;

    public function find($id): ?Link;

    public function make(): Link;

    public function save(Link $link): void;

    public function delete(Link $link): void;

    public function blueprint(): Blueprint;

    public static function bindings(): array;
}
