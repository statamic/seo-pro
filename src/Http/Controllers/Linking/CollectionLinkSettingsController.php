<?php

namespace Statamic\SeoPro\Http\Controllers\Linking;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Blueprints\CollectionConfigBlueprint;
use Statamic\SeoPro\Contracts\TextProcessing\ConfigurationRepository;
use Statamic\SeoPro\Http\Concerns\MergesBlueprintFields;
use Statamic\SeoPro\Http\Requests\UpdateCollectionBehaviorRequest;
use Statamic\SeoPro\TextProcessing\Config\CollectionConfig;

class CollectionLinkSettingsController extends CpController
{
    use MergesBlueprintFields;

    public function __construct(
        Request $request,
        protected readonly ConfigurationRepository $configurationRepository,
    ) {
        parent::__construct($request);
    }

    public function index()
    {
        if (request()->ajax()) {
            return $this->configurationRepository->getCollections()->map(fn (CollectionConfig $config) => $config->toArray());
        }

        return view('seo-pro::config.link_collections', $this->mergeBlueprintIntoContext(
            CollectionConfigBlueprint::blueprint(),
            callback: fn (&$values) => $values['allowed_collections'] = [],
        ));
    }

    public function update(UpdateCollectionBehaviorRequest $request, $collection)
    {
        abort_unless($collection, 404);

        $this->configurationRepository->updateCollectionConfiguration(
            $collection->handle(),
            new CollectionConfig(
                $collection->handle(),
                $collection->title(),
                request('linking_enabled'),
                request('allow_cross_site_linking'),
                request('allow_cross_collection_suggestions'),
                request('allowed_collections'),
            )
        );
    }

    public function resetConfig($collection)
    {
        abort_unless($collection, 404);

        $this->configurationRepository->resetCollectionConfiguration($collection);
    }
}
