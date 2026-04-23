<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Statamic\Console\Commands\Multisite;
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
use Statamic\SeoPro\Events\RedirectSaved;
use Statamic\SeoPro\Redirects\HandleRedirects;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\SeoPro\Redirects\RedirectRepository;
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
        Redirect::class => Policies\RedirectPolicy::class,
    ];

    protected $config = false;

    public function register()
    {
        $this->registerSerializableClasses([
            Page::class,
            Report::class,
            Redirect::class,
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
            ->bootStache()
            ->renderNotFoundHttpExceptions()
            ->bootRouteBindings()
            ->bootGit()
            ->bootAddonCommands()
            ->bootAddonGraphQL()
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
                            $nav->item(__('seo-pro::messages.site_defaults'))->route('seo-pro.site-defaults.edit')->can('edit seo site defaults'),
                            $nav->item(__('seo-pro::messages.section_defaults'))->route('seo-pro.section-defaults.index')->can('edit seo section defaults'),
                        ];
                    });
            }
        });

        return $this;
    }

    protected function bootAddonSubscriber()
    {
        Event::subscribe(Subscriber::class);
        Event::subscribe(Redirects\AutomaticRedirectSubscriber::class);

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

    protected function bootStache()
    {
        $this->app['stache']->registerStores([
            (new RedirectsStore)->directory(config('statamic.seo-pro.redirects.directory')),
        ]);

        $this->app->bind(Redirects\Stache\RedirectQueryBuilder::class, function () {
            return new Redirects\Stache\RedirectQueryBuilder($this->app->make(Stache::class)->store('redirects'));
        });

        Statamic::repository(
            RedirectRepository::class,
            Redirects\Stache\RedirectRepository::class,
        );

        return $this;
    }

    protected function renderNotFoundHttpExceptions()
    {
        NotFoundHttpException::renderUsing(fn ($request) => app(HandleRedirects::class)($request));

        return $this;
    }

    protected function bootRouteBindings()
    {
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

    protected function bootGit()
    {
        if (config('statamic.git.enabled')) {
            Git::listen(RedirectSaved::class);
        }

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

    protected function bootAddonCommands()
    {
        $this->commands([
            Commands\GenerateReportCommand::class,
        ]);

        return $this;
    }

    protected function bootAddonGraphQL()
    {
        GraphQL::addType(\Statamic\SeoPro\GraphQL\SeoProType::class);
        GraphQL::addType(\Statamic\SeoPro\GraphQL\AlternateLocaleType::class);

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
        });

        return $this;
    }

    private function userHasSeoPermissions()
    {
        $user = User::current();

        return $user->can('view seo reports')
            || $user->can('view seo redirects')
            || $user->can('edit seo site defaults')
            || $user->can('edit seo section defaults');
    }
}
