<?php

namespace Statamic\SeoPro\Linking\Queries;

use Statamic\Facades\Entry as EntryApi;
use Statamic\SeoPro\Contracts\Linking\ConfigurationRepository;

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
