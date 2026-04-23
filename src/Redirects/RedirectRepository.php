<?php

namespace Statamic\SeoPro\Redirects;

use Statamic\Fields\Blueprint;
use Illuminate\Support\Collection;

interface RedirectRepository
{
    public function all(): Collection;

    public function query(): RedirectQueryBuilder;

    public function find($id): ?Redirect;

    public function make(): Redirect;

    public function save(Redirect $redirect): void;

    public function delete(Redirect $redirect): void;

    public function blueprint(): Blueprint;

    public static function bindings(): array;
}
