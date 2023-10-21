<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\GraphQL;
use Statamic\Facades\Permission;
use Statamic\Facades\User;
use Statamic\Providers\AddonServiceProvider;
use Statamic\SeoPro;

class ServiceProvider extends AddonServiceProvider
{
    use GetsSectionDefaults;

    protected $tags = [
        SeoPro\Tags\SeoProTags::class,
    ];

    protected $fieldtypes = [
        SeoPro\Fieldtypes\SeoProFieldtype::class,
        SeoPro\Fieldtypes\SourceFieldtype::class,
    ];

    protected $widgets = [
        SeoPro\Widgets\SeoProWidget::class,
    ];

    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
        'hotFile' => __DIR__.'/../resources/dist/hot',
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
        'web' => __DIR__.'/../routes/web.php',
    ];

    protected $config = false;

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
            ->bootAddonCommands()
            ->bootAddonGraphQL();
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
                    ->icon('seo-search-graph')
                    ->children(function () use ($nav) {
                        return [
                            $nav->item(__('seo-pro::messages.reports'))->route('seo-pro.reports.index')->can('view seo reports'),
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

        return $this;
    }

    protected function bootAddonGlidePresets()
    {
        $server = app(\League\Glide\Server::class);

        $presets = collect([
            'seo_pro_twitter' => config('statamic.seo-pro.assets.twitter_preset'),
            'seo_pro_og' => config('statamic.seo-pro.assets.open_graph_preset'),
        ]);

        // The `twitter_graph_preset` was added later. If it's not set, gracefully
        // fall back so that existing sites generate off the original config.
        if (is_null($presets['seo_pro_twitter'])) {
            $presets['seo_pro_twitter'] = $presets['seo_pro_og'];
        }

        $server->setPresets($server->getPresets() + $presets->filter()->all());

        return $this;
    }

    protected function bootAddonCommands()
    {
        $this->commands([
            SeoPro\Commands\GenerateReportCommand::class,
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
                        ->with(SiteDefaults::load()->augmented())
                        ->with($this->getAugmentedSectionDefaults($item))
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

    private function userHasSeoPermissions()
    {
        $user = User::current();

        return $user->can('view seo reports')
            || $user->can('edit seo site defaults')
            || $user->can('edit seo section defaults');
    }
}
