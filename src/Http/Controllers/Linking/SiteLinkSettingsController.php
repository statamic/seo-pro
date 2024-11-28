<?php

namespace Statamic\SeoPro\Http\Controllers\Linking;

use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Auth\UserAccess;
use Statamic\SeoPro\Blueprints\SiteConfigBlueprint;
use Statamic\SeoPro\Contracts\TextProcessing\ConfigurationRepository;
use Statamic\SeoPro\Http\Concerns\MergesBlueprintFields;
use Statamic\SeoPro\Http\Requests\UpdateSiteConfigRequest;
use Statamic\SeoPro\TextProcessing\Config\SiteConfig;

class SiteLinkSettingsController extends CpController
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
        abort_unless(User::current()->can('edit link site'), 403);

        if (request()->ajax()) {
            return $this->configurationRepository->getSites()->map(fn (SiteConfig $config) => $config->toArray());
        }

        return view('seo-pro::config.sites', $this->mergeBlueprintIntoContext(
            SiteConfigBlueprint::blueprint(),
            callback: fn (&$values) => $values['ignored_phrases'] = [],
        ));
    }

    protected function assertHasAccessToSite($siteHandle)
    {
        abort_unless(User::current()->can('edit link sites'), 403);
        abort_unless($siteHandle != null, 404);
        abort_unless(UserAccess::getSitesForCurrentUser()->contains($siteHandle), 403);
    }

    public function update(UpdateSiteConfigRequest $request, $site)
    {
        $this->assertHasAccessToSite($site?->handle());

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
