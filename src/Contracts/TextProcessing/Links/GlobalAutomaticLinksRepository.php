<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Links;

interface GlobalAutomaticLinksRepository
{
    public function deleteAutomaticLinksForSite(string $handle): void;

}