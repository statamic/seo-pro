<?php

namespace Statamic\SeoPro\TextProcessing;

use Statamic\Facades\Entry as EntryApi;
use Statamic\SeoPro\Contracts\TextProcessing\ConfigurationRepository;

class EntryQuery
{
    public static function query()
    {
        /** @var ConfigurationRepository $config */
        $config = app(ConfigurationRepository::class);

        $disabledCollections = $config->getDisabledCollections();

        return EntryApi::query()
            ->whereStatus('published')
            ->whereNotIn('collection', $disabledCollections);
    }
}