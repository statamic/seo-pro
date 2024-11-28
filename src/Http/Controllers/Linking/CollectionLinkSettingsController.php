<?php

namespace Statamic\SeoPro\Http\Controllers\Linking;

use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Auth\UserAccess;
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
        abort_unless(User::current()->can('edit link collections'), 403);

        if (request()->ajax()) {
            $visibleCollections = UserAccess::getCollectionsForCurrentUser();

            return $this->configurationRepository
                ->getCollections()
                ->filter(fn (CollectionConfig $config) => $visibleCollections->contains($config->handle))
                ->map(fn (CollectionConfig $config) => $config->toArray());
        }

        return view('seo-pro::config.link_collections', $this->mergeBlueprintIntoContext(
            CollectionConfigBlueprint::blueprint(),
            callback: fn (&$values) => $values['allowed_collections'] = [],
        ));
    }

    protected function assertHasAccessToCollection($collectionHandle)
    {
        abort_unless(User::current()->can('edit link collections'), 403);
        abort_unless($collectionHandle != null, 404);
        abort_unless(UserAccess::getCollectionsForCurrentUser()->contains($collectionHandle), 403);
    }

    public function update(UpdateCollectionBehaviorRequest $request, $collection)
    {
        $this->assertHasAccessToCollection($collection?->handle());

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
        $this->assertHasAccessToCollection($collection);

        $this->configurationRepository->resetCollectionConfiguration($collection);
    }
}
