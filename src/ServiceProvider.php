<?php

namespace Statamic\SeoPro;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Statamic\Console\Commands\Multisite;
use Statamic\Console\Commands\StaticWarm;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Addon;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\File;
use Statamic\Facades\Git;
use Statamic\Facades\GraphQL;
use Statamic\Facades\Image;
use Statamic\Facades\Permission;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Providers\AddonServiceProvider;
use Statamic\SeoPro\Commands\GenerateReportCommand;
use Statamic\SeoPro\Commands\PurgeErrorsCommand;
use Statamic\SeoPro\Events\LlmsTxtGenerated;
use Statamic\SeoPro\Events\RedirectSaved;
use Statamic\SeoPro\GraphQL\AlternateLocaleType;
use Statamic\SeoPro\GraphQL\SeoProType;
use Statamic\SeoPro\Llms\Llms;
use Statamic\SeoPro\Redirects\Error;
use Statamic\SeoPro\Redirects\ErrorRepository;
use Statamic\SeoPro\Redirects\HandleRedirects;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\SeoPro\Redirects\RedirectRepository;
use Statamic\SeoPro\Redirects\Stache\ErrorsStore;
use Statamic\SeoPro\Redirects\Stache\RedirectsStore;
use Statamic\SeoPro\Reporting\Page;
use Statamic\SeoPro\Reporting\Report;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;
use Statamic\Stache\Stache;
use Statamic\Statamic;
use Statamic\Support\Str;

class ServiceProvider extends AddonServiceProvider
{
    use GetsSectionDefaults;

    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
        'hotFile' => __DIR__.'/../resources/dist/hot',
    ];

    protected $policies = [
        Error::class => Policies\ErrorPolicy::class,
        Redirect::class => Policies\RedirectPolicy::class,
    ];

    protected $config = false;

    public function register()
    {
        // Statamic discovers commands automatically, but only when running in the console.
        // To call this in a web request, we need to register it manually.
        $this->commands([GenerateReportCommand::class]);

        $this->registerSerializableClasses([
            Error::class,
            Page::class,
            Redirect::class,
            Report::class,
        ]);
    }

    public function bootAddon()
    {
        $this
            ->bootAddonConfig()
            ->bootAddonViews()
            ->bootAddonBladeDirective()
            ->bootAddonPermissions()
            ->bootAddonNav()
            ->bootAddonSubscriber()
            ->bootAddonGlidePresets()
            ->bootRedirects()
            ->bootRouteBindings()
            ->bootGit()
            ->bootAddonScheduledCommands()
            ->bootAddonGraphQL()
            ->bootLlmsTxt()
            ->bootMultisiteCommandHook();
    }

    protected function bootAddonConfig()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/seo-pro.php', 'statamic.seo-pro');

        $this->publishes([
            __DIR__.'/../config/seo-pro.php' => config_path('statamic/seo-pro.php'),
        ], 'seo-pro-config');

        return $this;
    }

    protected function bootAddonViews()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/generated', 'seo-pro');

        $this->publishes([
            __DIR__.'/../resources/views/generated' => resource_path('views/vendor/seo-pro'),
        ], 'seo-pro-views');

        return $this;
    }

    protected function bootAddonBladeDirective()
    {
        Blade::directive('seo_pro', function ($tag) {
            return '<?php echo \Facades\Statamic\SeoPro\Directives\SeoProDirective::renderTag('.$tag.', $__data) ?>';
        });

        return $this;
    }

    protected function bootAddonPermissions()
    {
        Permission::group('seo_pro', 'SEO Pro', function () {
            Permission::register('view seo redirects', function ($permission) {
                $permission
                    ->label(__('seo-pro::messages.view_redirects'))
                    ->children([
                        Permission::make('edit seo redirects')
                            ->label(__('seo-pro::messages.edit_redirects'))
                            ->children([
                                Permission::make('create seo redirects')->label(__('seo-pro::messages.create_redirects')),
                                Permission::make('delete seo redirects')->label(__('seo-pro::messages.delete_redirects')),
                            ]),
                    ]);
            });
            Permission::register('view seo reports', function ($permission) {
                $permission->children([
                    Permission::make('delete seo reports')->label(__('seo-pro::messages.delete_reports')),
                ]);
            })->label(__('seo-pro::messages.view_reports'));
            Permission::register('edit seo site defaults')->label(__('seo-pro::messages.edit_site_defaults'));
            Permission::register('edit seo section defaults')->label(__('seo-pro::messages.edit_section_defaults'));
            Permission::register('edit seo robots')->label(__('seo-pro::messages.edit_robots'));
        });

        return $this;
    }

    protected function bootAddonNav()
    {
        Nav::extend(function ($nav) {
            if ($this->userHasSeoPermissions()) {
                $nav->tools('SEO Pro')
                    ->route('seo-pro.index')
                    ->icon(File::get(__DIR__.'/../resources/svg/nav-icon.svg'))
                    ->children(function () use ($nav) {
                        return [
                            $nav->item(__('seo-pro::messages.reports'))->route('seo-pro.reports.index')->can('view seo reports'),
                            $nav->item(__('seo-pro::messages.redirects'))->route('seo-pro.redirects.index')->can('view seo redirects'),
                            $nav->item(__('seo-pro::messages.errors'))->route('seo-pro.errors.index')->can('view seo redirects'),
                            $nav->item(__('seo-pro::messages.site_defaults'))->route('seo-pro.site-defaults.edit')->can('edit seo site defaults'),
                            $nav->item(__('seo-pro::messages.section_defaults'))->route('seo-pro.section-defaults.index')->can('edit seo section defaults'),
                            $nav->item(__('seo-pro::messages.robots'))->route('seo-pro.robots.edit')->can('edit seo robots'),
                        ];
                    });
            }
        });

        return $this;
    }

    protected function bootAddonSubscriber()
    {
        Event::subscribe(Subscriber::class);

        if (config('statamic.seo-pro.redirects.automatic_redirects.enabled')) {
            Event::subscribe(Redirects\AutomaticRedirectSubscriber::class);
        }

        return $this;
    }

    protected function bootAddonGlidePresets()
    {
        $presets = collect([
            'seo_pro_twitter' => config('statamic.seo-pro.assets.twitter_preset'),
            'seo_pro_og' => config('statamic.seo-pro.assets.open_graph_preset'),
        ]);

        // The `twitter_graph_preset` was added later. If it's not set, gracefully
        // fall back so that existing sites generate off the original config.
        if (is_null($presets['seo_pro_twitter'])) {
            $presets['seo_pro_twitter'] = $presets['seo_pro_og'];
        }

        Image::registerCustomManipulationPresets($presets->filter()->all());

        return $this;
    }

    protected function bootRedirects()
    {
        $this->app['stache']->registerStores([
            (new ErrorsStore)->directory(config('statamic.seo-pro.redirects.errors.directory')),
            (new RedirectsStore)->directory(config('statamic.seo-pro.redirects.directory')),
        ]);

        $this->app->bind(Redirects\Stache\ErrorQueryBuilder::class, function () {
            return new Redirects\Stache\ErrorQueryBuilder($this->app->make(Stache::class)->store('seo_pro_errors'));
        });

        $this->app->bind(Redirects\Stache\RedirectQueryBuilder::class, function () {
            return new Redirects\Stache\RedirectQueryBuilder($this->app->make(Stache::class)->store('seo_pro_redirects'));
        });

        Statamic::repository(ErrorRepository::class, Redirects\Stache\ErrorRepository::class);
        Statamic::repository(RedirectRepository::class, Redirects\Stache\RedirectRepository::class);

        if (config('statamic.seo-pro.redirects.driver') === 'database') {
            $this->app['stache']->exclude('seo_pro_redirects');

            Statamic::repository(RedirectRepository::class, Redirects\Eloquent\RedirectRepository::class);
        }

        if (config('statamic.seo-pro.redirects.errors.driver') === 'database') {
            $this->app['stache']->exclude('seo_pro_errors');

            Statamic::repository(ErrorRepository::class, Redirects\Eloquent\ErrorRepository::class);
        }

        NotFoundHttpException::renderUsing(fn ($request) => app(HandleRedirects::class)($request));

        return $this;
    }

    protected function bootRouteBindings(): static
    {
        Route::bind('error', function ($id, $route = null) {
            if (! $route || (! $this->isCpRoute($route) && ! $this->isFrontendBindingEnabled())) {
                return $id;
            }

            $field = $route->bindingFieldFor('error') ?? 'id';

            return $field == 'id'
                ? Facades\Error::find($id)
                : Facades\Error::query()->where($field, $id)->first();
        });

        Route::bind('redirect', function ($id, $route = null) {
            if (! $route || (! $this->isCpRoute($route) && ! $this->isFrontendBindingEnabled())) {
                return $id;
            }

            $field = $route->bindingFieldFor('redirect') ?? 'id';

            return $field == 'id'
                ? Facades\Redirect::find($id)
                : Facades\Redirect::query()->where($field, $id)->first();
        });

        return $this;
    }

    private function isCpRoute(\Illuminate\Routing\Route $route): bool
    {
        $cp = Str::ensureRight(config('statamic.cp.route'), '/');

        if ($cp === '/') {
            return true;
        }

        return Str::startsWith($route->uri(), $cp);
    }

    private function isFrontendBindingEnabled(): bool
    {
        return config('statamic.routes.bindings', false);
    }

    protected function bootGit()
    {
        if (config('statamic.git.enabled')) {
            config()->set('statamic.git.paths', array_values(array_unique([
                ...config('statamic.git.paths', []),
                public_path('robots.txt'),
                ...Site::all()->map(fn ($site) => public_path(Llms::relativePath($site)))->all(),
            ])));

            Git::listen(RedirectSaved::class);
            Git::listen(LlmsTxtGenerated::class);
        }

        return $this;
    }

    protected function bootLlmsTxt()
    {
        StaticWarm::hook('additional', function ($urls, $next) {
            return $next($urls->merge(Llms::enabledSites()->map(fn ($site) => Llms::url($site))));
        });

        return $this;
    }

    protected function bootAddonScheduledCommands()
    {
        if (config('statamic.seo-pro.redirects.errors.enabled')) {
            $this->app->make(Schedule::class)->command(PurgeErrorsCommand::class)->daily();
        }

        return $this;
    }

    protected function bootAddonGraphQL()
    {
        GraphQL::addType(SeoProType::class);
        GraphQL::addType(AlternateLocaleType::class);

        $seoField = function () {
            return [
                'type' => GraphQL::type('SeoPro'),
                'resolve' => function ($item) {
                    return (new Cascade)
                        ->withSiteDefaults(SiteDefaults::in($item->locale())->augmented())
                        ->withSectionDefaults($this->getAugmentedSectionDefaults($item))
                        ->with($item->seo)
                        ->withCurrent($item)
                        ->get();
                },
            ];
        };

        GraphQL::addField('EntryInterface', 'seo', $seoField);
        GraphQL::addField('TermInterface', 'seo', $seoField);

        return $this;
    }

    protected function bootMultisiteCommandHook()
    {
        Multisite::hook('after', function () {
            $settings = Addon::get('statamic/seo-pro')->settings();

            $settings->set([
                'site_defaults' => [
                    Site::default()->handle() => $settings->get('site_defaults', []),
                ],
                'site_defaults_sites' => [
                    Site::default()->handle() => null,
                ],
            ]);

            $settings->save();

            if (config('statamic.seo-pro.redirects.driver') === 'file') {
                $this->components->task(
                    description: 'Updating redirects',
                    task: function () {
                        $base = app(Stache::class)->store('seo_pro_redirects')->directory();

                        File::makeDirectory("{$base}/{$this->siteHandle}");

                        File::getFiles($base)->each(function ($file) use ($base) {
                            $filename = pathinfo($file, PATHINFO_BASENAME);
                            File::move($file, "{$base}/{$this->siteHandle}/{$filename}");
                        });
                    }
                );
            }

            if (config('statamic.seo-pro.redirects.errors.driver') === 'file') {
                $this->components->task(
                    description: 'Updating errors',
                    task: function () {
                        $base = app(Stache::class)->store('seo_pro_errors')->directory();

                        File::makeDirectory("{$base}/{$this->siteHandle}");

                        File::getFiles($base)->each(function ($file) use ($base) {
                            $filename = pathinfo($file, PATHINFO_BASENAME);
                            File::move($file, "{$base}/{$this->siteHandle}/{$filename}");
                        });
                    }
                );
            }
        });

        return $this;
    }

    private function userHasSeoPermissions()
    {
        $user = User::current();

        return $user->can('view seo reports')
            || $user->can('view seo redirects')
            || $user->can('edit seo site defaults')
            || $user->can('edit seo section defaults')
            || $user->can('edit seo robots');
    }
}
