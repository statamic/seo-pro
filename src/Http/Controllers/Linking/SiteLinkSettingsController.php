<?php

namespace Statamic\SeoPro\Http\Controllers\Linking;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Contracts\TextProcessing\ConfigurationRepository;
use Statamic\SeoPro\Http\Concerns\MergesBlueprintFields;
use Statamic\SeoPro\Http\Requests\UpdateSiteConfigRequest;
use Statamic\SeoPro\TextProcessing\Config\SiteConfig;
use Statamic\SeoPro\TextProcessing\Config\SiteConfigBlueprint;

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
        if (request()->ajax()) {
            return $this->configurationRepository->getSites()->map(fn (SiteConfig $config) => $config->toArray());
        }

        return view('seo-pro::config.sites', $this->mergeBlueprintIntoContext(
            SiteConfigBlueprint::blueprint(),
            callback: fn (&$values) => $values['ignored_phrases'] = [],
        ));
    }

    public function update(UpdateSiteConfigRequest $request, $site)
    {
        abort_unless($site, 404);

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
        abort_unless($site, 404);

        $this->configurationRepository->resetSiteConfiguration($site);
    }
}
