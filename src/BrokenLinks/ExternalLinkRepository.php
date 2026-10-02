<?php

namespace Statamic\SeoPro\BrokenLinks;

use Illuminate\Support\Collection;

interface ExternalLinkRepository
{
    public function all(): Collection;

    public function query(): ExternalLinkQueryBuilder;

    public function find($id): ?ExternalLink;

    public function findByUrl(string $url, string $site): ?ExternalLink;

    public function make(): ExternalLink;

    public function save(ExternalLink $link): void;

    public function delete(ExternalLink $link): void;

    public static function bindings(): array;
}
