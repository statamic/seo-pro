<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Statamic\Events\CollectionDeleted;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\SiteDeleted;
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

    protected $scopes = [
        Query\Scopes\Filters\Collection::class,
        Query\Scopes\Filters\Site::class,
        Query\Scopes\Filters\Fields::class,
    ];

    protected $config = false;

    public function bootAddon()
    {
        $this
            ->bootAddonConfig()
            ->bootAddonMigrations()
            ->bootAddonViews()
            ->bootAddonBladeDirective()
            ->bootAddonPermissions()
            ->bootAddonNav()
            ->bootAddonSubscriber()
            ->bootAddonGlidePresets()
            ->bootAddonCommands()
            ->bootAddonGraphQL()
            ->bootTextAnalysis()
            ->bootEvents();
    }

    protected function isLinkingEnabled(): bool
    {
        return config('statamic.seo-pro.linking.enabled', false);
    }

    public function bootEvents()
    {
        if ($this->isLinkingEnabled()) {
            $this->listen = array_merge($this->listen, [
                EntrySaved::class => [
                    SeoPro\Listeners\EntrySavedListener::class,
                ],
                EntryDeleted::class => [
                    SeoPro\Listeners\EntryDeletedListener::class,
                ],
                SiteDeleted::class => [
                    SeoPro\Listeners\SiteDeletedListener::class,
                ],
                CollectionDeleted::class => [
                    SeoPro\Listeners\CollectionDeletedListener::class,
                ],
                SeoPro\Events\InternalLinksUpdated::class => [
                    SeoPro\Listeners\InternalLinksUpdatedListener::class,
                ],
            ]);
        }

        return parent::bootEvents();
    }

    protected function bootTextAnalysis()
    {
        if (! $this->isLinkingEnabled()) {
            return $this;
        }

        SeoPro\Actions\ViewLinkSuggestions::register();

        $this->app->bind(
            Contracts\TextProcessing\Content\ContentRetriever::class,
            config('statamic.seo-pro.linking.drivers.content'),
        );

        $this->app->bind(
            Contracts\TextProcessing\Content\Tokenizer::class,
            config('statamic.seo-pro.linking.drivers.tokenizer'),
        );

        $this->app->bind(
            Contracts\TextProcessing\Embeddings\Extractor::class,
            config('statamic.seo-pro.linking.drivers.embeddings'),
        );

        $this->app->bind(
            Contracts\TextProcessing\Keywords\KeywordRetriever::class,
            config('statamic.seo-pro.linking.drivers.keywords'),
        );

        $this->app->bind(
            Contracts\TextProcessing\ConfigurationRepository::class,
            TextProcessing\Config\ConfigurationRepository::class,
        );

        $this->app->bind(
            Contracts\TextProcessing\Keywords\KeywordsRepository::class,
            TextProcessing\Keywords\KeywordsRepository::class,
        );

        $this->app->bind(
            Contracts\TextProcessing\Links\LinkCrawler::class,
            config('statamic.seo-pro.linking.drivers.link_scanner'),
        );

        $this->app->bind(
            Contracts\TextProcessing\Embeddings\EntryEmbeddingsRepository::class,
            TextProcessing\Embeddings\EmbeddingsRepository::class,
        );

        $this->app->bind(
            Contracts\TextProcessing\Links\GlobalAutomaticLinksRepository::class,
            TextProcessing\Links\GlobalAutomaticLinksRepository::class,
        );

        $this->app->bind(
            Contracts\TextProcessing\Links\LinksRepository::class,
            TextProcessing\Links\LinkRepository::class,
        );

        $this->app->singleton(SeoPro\TextProcessing\Content\ContentMapper::class, function () {
            return new SeoPro\TextProcessing\Content\ContentMapper;
        });

        $this->app->singleton(SeoPro\TextProcessing\Content\LinkReplacer::class, function () {
            return new SeoPro\TextProcessing\Content\LinkReplacer(
                app(SeoPro\TextProcessing\Content\ContentMapper::class),
            );
        });

        return $this->registerDefaultFieldtypeReplacers()
            ->registerDefaultContentMappers();
    }

    protected function registerDefaultFieldtypeReplacers(): static
    {
        /** @var SeoPro\TextProcessing\Content\LinkReplacer $linkReplacer */
        $linkReplacer = $this->app->make(SeoPro\TextProcessing\Content\LinkReplacer::class);

        $linkReplacer->registerReplacers([
            SeoPro\TextProcessing\Content\LinkReplacers\MarkdownReplacer::class,
            SeoPro\TextProcessing\Content\LinkReplacers\TextReplacer::class,
            SeoPro\TextProcessing\Content\LinkReplacers\TextareaReplacer::class,
            SeoPro\TextProcessing\Content\LinkReplacers\Bard\BardReplacer::class,
        ]);

        return $this;
    }

    protected function registerDefaultContentMappers(): static
    {
        /** @var SeoPro\TextProcessing\Content\ContentMapper $contentMapper */
        $contentMapper = $this->app->make(SeoPro\TextProcessing\Content\ContentMapper::class);

        $contentMapper->registerMappers([
            SeoPro\TextProcessing\Content\Mappers\TextFieldMapper::class,
            SeoPro\TextProcessing\Content\Mappers\TextareaFieldMapper::class,
            SeoPro\TextProcessing\Content\Mappers\MarkdownFieldMapper::class,
            SeoPro\TextProcessing\Content\Mappers\GridFieldMapper::class,
            SeoPro\TextProcessing\Content\Mappers\ReplicatorFieldMapper::class,
            SeoPro\TextProcessing\Content\Mappers\BardFieldMapper::class,
            SeoPro\TextProcessing\Content\Mappers\GroupFieldMapper::class,
        ]);

        return $this;
    }

    protected function bootAddonConfig()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/seo-pro.php', 'statamic.seo-pro');

        $this->publishes([
            __DIR__.'/../config/seo-pro.php' => config_path('statamic/seo-pro.php'),
        ], 'seo-pro-config');

        return $this;
    }

    protected function bootAddonMigrations()
    {
        $this->publishes([
            __DIR__.'/../database/migrations/2024_07_26_184745_create_seopro_entry_embeddings_table.php' => database_path('migrations/2024_07_26_184745_create_seopro_entry_embeddings_table.php'),
            __DIR__.'/../database/migrations/2024_08_10_154109_create_seopro_entry_links_table.php' => database_path('migrations/2024_08_10_154109_create_seopro_entry_links_table.php'),
            __DIR__.'/../database/migrations/2024_08_17_123712_create_seopro_entry_keywords_table.php' => database_path('migrations/2024_08_17_123712_create_seopro_entry_keywords_table.php'),
            __DIR__.'/../database/migrations/2024_09_02_135012_create_seopro_site_link_settings_table.php' => database_path('migrations/2024_09_02_135012_create_seopro_site_link_settings_table.php'),
            __DIR__.'/../database/migrations/2024_09_02_135056_create_seopro_global_automatic_links_table.php' => database_path('migrations/2024_09_02_135056_create_seopro_global_automatic_links_table.php'),
            __DIR__.'/../database/migrations/2024_09_03_102233_create_seopro_collection_link_settings_table.php' => database_path('migrations/2024_09_03_102233_create_seopro_collection_link_settings_table.php'),
        ], 'seo-pro-migrations');

        return $this;
    }

    protected function bootAddonViews()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/generated', 'seo-pro');
        $this->loadViewsFrom(__DIR__.'/../resources/views/links', 'seo-pro');

        $this->publishes([
            __DIR__.'/../resources/views/generated' => resource_path('views/vendor/seo-pro'),
            __DIR__.'/../resources/views/links' => resource_path('views/vendor/seo-pro/links'),
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
                        $menuItems = [
                            $nav->item(__('seo-pro::messages.reports'))->route('seo-pro.reports.index')->can('view seo reports'),
                            $nav->item(__('seo-pro::messages.site_defaults'))->route('seo-pro.site-defaults.edit')->can('edit seo site defaults'),
                            $nav->item(__('seo-pro::messages.section_defaults'))->route('seo-pro.section-defaults.index')->can('edit seo section defaults'),
                        ];

                        if ($this->isLinkingEnabled()) {
                            $menuItems[] = $nav->item(__('seo-pro::messages.link_manager'))->route('seo-pro.internal-links.index')->can('view seo links');
                        }

                        return $menuItems;
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
            SeoPro\Commands\GenerateEmbeddingsCommand::class,
            SeoPro\Commands\GenerateKeywordsCommand::class,
            SeoPro\Commands\ScanLinksCommand::class,
            SeoPro\Commands\StartTheEnginesCommand::class,
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
