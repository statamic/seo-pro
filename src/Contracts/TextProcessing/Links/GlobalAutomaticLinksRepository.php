<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Links;

use Illuminate\Support\Collection;

interface GlobalAutomaticLinksRepository
{
    public function deleteAutomaticLinksForSite(string $handle): void;

    public function getLinksForSite(string $handle): Collection;

}