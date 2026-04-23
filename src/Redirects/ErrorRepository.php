<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;

interface ErrorRepository
{
    public function all(): Collection;

    public function query(): ErrorQueryBuilder;

    public function find($id): ?Error;

    public function make(): Error;

    public function save(Error $error): void;

    public function delete(Error $error): void;

    public function blueprint(): Blueprint;

    public static function bindings(): array;
}
