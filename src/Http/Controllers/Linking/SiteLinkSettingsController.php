<?php

namespace Statamic\SeoPro\Http\Controllers\Linking;

use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Auth\UserAccess;
use Statamic\SeoPro\Blueprints\SiteConfigBlueprint;
use Statamic\SeoPro\Contracts\TextProcessing\ConfigurationRepository;
use Statamic\SeoPro\Http\Concerns\MergesBlueprintFields;
use Statamic\SeoPro\Http\Concerns\ResolvesPermissions;
use Statamic\SeoPro\Http\Resources\Links\SiteConfigCollection;
use Statamic\SeoPro\Http\ValuesResponse;
use Statamic\SeoPro\TextProcessing\Config\SiteConfig;

class SiteLinkSettingsController extends CpController
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
        abort_unless(User::current()->can('edit link site'), 403);

        if (request()->ajax()) {
            $visibleSites = UserAccess::getSitesForCurrentUser();

            $sites = $this->configurationRepository
                ->getSites()
                ->filter(fn (SiteConfig $config) => $visibleSites->contains($config->handle));

            return (new SiteConfigCollection($sites))
                ->blueprint(SiteConfigBlueprint::make());
        }

        return view('seo-pro::config.sites', $this->mergeBlueprintIntoContext(
            SiteConfigBlueprint::make(),
            $this->getLinkPermissions(),
            callback: fn (&$values) => $values['ignored_phrases'] = [],
        ));
    }

    protected function assertHasAccessToSite($siteHandle)
    {
        abort_unless(User::current()->can('edit link sites'), 403);
        abort_unless($siteHandle != null, 404);
        abort_unless(UserAccess::getSitesForCurrentUser()->contains($siteHandle), 403);
    }

    public function getValues(Request $request, $site)
    {
        $this->assertHasAccessToSite($site?->handle());

        /** @var ConfigurationRepository $siteConfig */
        $siteConfig = app(ConfigurationRepository::class);
        $config = $siteConfig->getSiteConfiguration($site->handle());

        return new ValuesResponse(
            SiteConfigBlueprint::make(),
            $config->toArray(),
        );
    }

    public function update(Request $request, $site)
    {
        $this->assertHasAccessToSite($site?->handle());

        SiteConfigBlueprint::make()
            ->fields()
            ->addValues($request->all())
            ->validate();

        $this->configurationRepository->updateSiteConfiguration(
            $site->handle(),
            new SiteConfig(
                $site->handle(),
                '',
                request('ignored_phrases') ?? [],
                (int) request('keyword_threshold'),
                (int) request('min_internal_links'),
                (int) request('max_internal_links'),
                (int) request('min_external_links'),
                (int) request('max_external_links'),
                request('prevent_circular_links'),
            )
        );
    }

    public function resetConfig($site)
    {
        $this->assertHasAccessToSite($site);

        $this->configurationRepository->resetSiteConfiguration($site);
    }
}
