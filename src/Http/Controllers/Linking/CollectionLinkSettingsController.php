<?php

namespace Statamic\SeoPro\Http\Controllers\Linking;

use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Auth\UserAccess;
use Statamic\SeoPro\Blueprints\CollectionConfigBlueprint;
use Statamic\SeoPro\Contracts\Linking\ConfigurationRepository;
use Statamic\SeoPro\Http\Concerns\MergesBlueprintFields;
use Statamic\SeoPro\Http\Concerns\ResolvesPermissions;
use Statamic\SeoPro\Http\Resources\Links\CollectionConfigCollection;
use Statamic\SeoPro\Http\ValuesResponse;
use Statamic\SeoPro\Linking\Config\CollectionConfig;

class CollectionLinkSettingsController extends CpController
{
    use MergesBlueprintFields, ResolvesPermissions;

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

            $collections = $this->configurationRepository
                ->getCollections()
                ->filter(fn (CollectionConfig $config) => $visibleCollections->contains($config->handle));

            return (new CollectionConfigCollection($collections))
                ->blueprint(CollectionConfigBlueprint::make());
        }

        return view('seo-pro::config.link_collections', $this->mergeBlueprintIntoContext(
            CollectionConfigBlueprint::make(),
            $this->getLinkPermissions(),
            callback: fn (&$values) => $values['allowed_collections'] = [],
        ));
    }

    protected function assertHasAccessToCollection($collectionHandle)
    {
        abort_unless(User::current()->can('edit link collections'), 403);
        abort_unless($collectionHandle != null, 404);
        abort_unless(UserAccess::getCollectionsForCurrentUser()->contains($collectionHandle), 403);
    }

    public function getValues($collection)
    {
        $this->assertHasAccessToCollection($collection?->handle());

        /** @var ConfigurationRepository $collectionConfig */
        $collectionConfig = app(ConfigurationRepository::class);
        $config = $collectionConfig->getCollectionConfiguration($collection->handle());

        return new ValuesResponse(
            CollectionConfigBlueprint::make(),
            $config->toArray(),
        );
    }

    public function update(Request $request, $collection)
    {
        $this->assertHasAccessToCollection($collection?->handle());

        CollectionConfigBlueprint::make()
            ->fields()
            ->addValues($request->all())
            ->validate();

        $this->configurationRepository->updateCollectionConfiguration(
            $collection->handle(),
            new CollectionConfig(
                $collection->handle(),
                '',
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
