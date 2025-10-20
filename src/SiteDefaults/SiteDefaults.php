<?php

namespace Statamic\SeoPro\SiteDefaults;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Facades\Addon;
use Statamic\Facades\Blink;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Site;
use Statamic\SeoPro\Events\SiteDefaultsSaved;
use Statamic\SeoPro\Fields;
use Statamic\SeoPro\HasAssetField;

// todo: stop extending Collection
class SiteDefaults extends Collection
{
    use HasAssetField;

    // todo: rename
    public static function newGet(): Collection
    {
        $data = Addon::get('statamic/seo-pro')->settings()->get('site_defaults', []);

        // TODO: Should these defaults not exist on the blueprint fields?
        if (empty($data)) {
            $data = Site::multiEnabled() ? [Site::current()->handle() => self::defaultValues()] : self::defaultValues();
        }

        return Site::all()->map(function ($site) use ($data) {
            $values = Arr::get($data, Site::multiEnabled() ? $site->handle() : '', []);

            return new LocalizedSiteDefaults($site->handle(), collect($values));
        });
    }

    public static function origins(): Collection
    {
        return config()->collection(
            key: 'statamic.seo-pro.site_defaults.origins',
            default: Site::all()->mapWithKeys(fn ($site) => [$site->handle() => null])->all()
        );
    }

    public static function in(string $locale): ?LocalizedSiteDefaults
    {
        if (! self::newGet()->has($locale)) {
            return null;
        }

        return self::newGet()->get($locale);
    }

    // todo: rename
    public static function newSave(LocalizedSiteDefaults $localized)
    {
        $data = Addon::get('statamic/seo-pro')->settings()->get('site_defaults', []);

        if (Site::multiEnabled()) {
            $data[$localized->locale()] = $localized->all();
        } else {
            $data = $localized->all();
        }

        Addon::get('statamic/seo-pro')->settings()->set('site_defaults', $data)->save();

        return true;
    }





    /**
     * Load site defaults collection.
     *
     * @param  array|Collection|null  $items
     */
    public function __construct($items = null)
    {
        if (! is_null($items)) {
            $items = collect($items)->all();
        }

        $this->items = $items ?? $this->getDefaults();
    }

    /**
     * @deprecated
     *
     * Load site defaults collection.
     *
     * @param  array|Collection|null  $items
     * @return static
     */
    public static function load($items = null)
    {
        $class = app(SiteDefaults::class);

        return new $class($items);
    }

    /**
     * @deprecated
     *
     * Get augmented.
     *
     * @return array
     */
    public function augmented()
    {
        $contentValues = Blueprint::make()
            ->setContents(['tabs' => ['main' => ['sections' => Fields::new()->getConfig()]]])
            ->fields()
            ->addValues($this->items)
            ->augment()
            ->values();

        $defaultValues = $this->blueprint()
            ->fields()
            ->addValues($this->items)
            ->augment()
            ->values();

        return $defaultValues
            ->merge($contentValues)
            ->only(array_keys($this->items))
            ->all();
    }

    /**
     * @deprecated
     *
     * Save site defaults collection to yaml.
     */
    public function save()
    {
        Addon::get('statamic/seo-pro')->settings()->set('site_defaults', $this->items)->save();

        Blink::forget('seo-pro::defaults');
    }

    /**
     * Get site defaults from yaml.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return Blink::once('seo-pro::defaults', function () {
            return [
                ...self::defaultValues(),
                ...Addon::get('statamic/seo-pro')->settings()->get('site_defaults', []),
            ];
        });
    }

    /**
     * The default values to be merged into the site's values.
     *
     * @return array
     */
    private static function defaultValues(): array
    {
        return [
            'site_name' => 'Site Name',
            'site_name_position' => 'after',
            'site_name_separator' => '|',
            'title' => '@seo:title',
            'description' => '@seo:content',
            'canonical_url' => '@seo:permalink',
            'priority' => 0.5,
            'change_frequency' => 'monthly',
        ];
    }

    /**
     * Get site defaults blueprint.
     *
     * @return \Statamic\Fields\Blueprint
     */
    public function blueprint()
    {
        // todo: move this to its own class

        return Blueprint::make()->setContents([
            'tabs' => [
                'meta' => [
                    'display' => __('seo-pro::messages.meta'),
                    'sections' => [
                        [
                            'display' => __('seo-pro::fieldsets/defaults.meta_section'),
                            'instructions' => __('seo-pro::fieldsets/defaults.meta_section_instruct'),
                            'fields' => [
                                [
                                    'handle' => 'title',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.title'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.title_instruct'),
                                        'type' => 'seo_pro_source',
                                        'inherit' => false,
                                        'localizable' => true,
                                        'field' => [
                                            'type' => 'text',
                                        ],
                                    ],
                                ],
                                [
                                    'handle' => 'description',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.description'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.description_instruct'),
                                        'type' => 'seo_pro_source',
                                        'inherit' => false,
                                        'localizable' => true,
                                        'field' => [
                                            'type' => 'textarea',
                                        ],
                                    ],
                                ],
                                [
                                    'handle' => 'site_name',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.site_name'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.site_name_instruct'),
                                        'type' => 'text',
                                        'localizable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'site_name_position',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.site_name_position'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.site_name_position_instruct'),
                                        'type' => 'select',
                                        'options' => [
                                            'after' => __('seo-pro::messages.after'),
                                            'before' => __('seo-pro::messages.before'),
                                            'none' => __('seo-pro::messages.disable'),
                                        ],
                                        'width' => 50,
                                        'localizable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'site_name_separator',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.site_name_separator'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.site_name_separator_instruct'),
                                        'type' => 'text',
                                        'width' => 50,
                                        'localizable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'canonical_url',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.canonical_url'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.canonical_url_instruct'),
                                        'type' => 'seo_pro_source',
                                        'inherit' => false,
                                        'field' => false,
                                        'localizable' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'robots' => [
                    'display' => __('seo-pro::fieldsets/defaults.robots_section'),
                    'instructions' => __('seo-pro::fieldsets/defaults.robots_section_instruct'),
                    'sections' => [
                        [
                            'display' => __('seo-pro::fieldsets/defaults.robots_section'),
                            'instructions' => __('seo-pro::fieldsets/defaults.robots_section_instruct'),
                            'fields' => [
                                [
                                    'handle' => 'robots_indexing',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.robots_indexing'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.robots_indexing_instruct'),
                                        'type' => 'button_group',
                                        'options' => [
                                            'index' => 'Index',
                                            'noindex' => 'Noindex',
                                        ],
                                        'default' => 'index',
                                        'localizable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'robots_following',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.robots_following'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.robots_following_instruct'),
                                        'type' => 'button_group',
                                        'options' => [
                                            'follow' => 'Follow',
                                            'nofollow' => 'Nofollow',
                                        ],
                                        'default' => 'follow',
                                        'localizable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'robots_noarchive',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.robots_noarchive'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.robots_noarchive_instruct'),
                                        'type' => 'toggle',
                                        'localizable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'robots_noimageindex',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.robots_noimageindex'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.robots_noimageindex_instruct'),
                                        'type' => 'toggle',
                                        'localizable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'robots_nosnippet',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.robots_nosnippet'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.robots_nosnippet_instruct'),
                                        'type' => 'toggle',
                                        'localizable' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'opengraph' => [
                    'display' => __('seo-pro::messages.opengraph'),
                    'sections' => [
                        [
                            'fields' => [
                                [
                                    'handle' => 'og_title',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.og_title'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.og_title_instruct'),
                                        'type' => 'seo_pro_source',
                                        'inherit' => false,
                                        'localizable' => true,
                                        'field' => [
                                            'type' => 'text',
                                        ],
                                        'default' => '@seo:title',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'display' => __('seo-pro::fieldsets/defaults.image_section'),
                            'instructions' => __('seo-pro::fieldsets/defaults.image_section_instruct'),
                            'fields' => [
                                [
                                    'handle' => 'image',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.image'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.image_instruct'),
                                        'type' => 'seo_pro_source',
                                        'inherit' => false,
                                        'default' => false,
                                        'disableable' => true,
                                        'allowed_fieldtypes' => [
                                            'assets',
                                        ],
                                        'localizable' => true,
                                        'field' => static::getAssetFieldConfig(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'social' => [
                    'display' => __('seo-pro::messages.social'),
                    'sections' => [
                        [
                            'display' => __('seo-pro::fieldsets/defaults.social_section'),
                            'instructions' => __('seo-pro::fieldsets/defaults.social_section_instruct'),
                            'fields' => [
                                [
                                    'handle' => 'twitter_handle',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.twitter_handle'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.twitter_handle_instruct'),
                                        'type' => 'text',
                                        'localizable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'twitter_title',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.twitter_title'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.twitter_title_instruct'),
                                        'type' => 'seo_pro_source',
                                        'inherit' => false,
                                        'localizable' => true,
                                        'field' => [
                                            'type' => 'text',
                                        ],
                                        'default' => '@seo:title',
                                    ],
                                ],
                                [
                                    'handle' => 'twitter_description',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.twitter_description'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.twitter_description_instruct'),
                                        'type' => 'seo_pro_source',
                                        'inherit' => false,
                                        'localizable' => true,
                                        'field' => [
                                            'type' => 'textarea',
                                        ],
                                        'default' => '@seo:description',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'sitemap' => [
                    'display' => __('seo-pro::messages.sitemap'),
                    'sections' => [
                        [
                            'display' => __('seo-pro::fieldsets/defaults.sitemap_section'),
                            'instructions' => __('seo-pro::fieldsets/defaults.sitemap_section_instruct'),
                            'fields' => [
                                [
                                    'handle' => 'priority',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.priority'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.priority_instruct'),
                                        'type' => 'seo_pro_source',
                                        'inherit' => false,
                                        'localizable' => true,
                                        'field' => [
                                            'type' => 'text',
                                        ],
                                    ],
                                ],
                                [
                                    'handle' => 'change_frequency',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.change_frequency'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.change_frequency_instruct'),
                                        'type' => 'seo_pro_source',
                                        'inherit' => false,
                                        'localizable' => true,
                                        'field' => [
                                            'type' => 'select',
                                            'options' => [
                                                'hourly' => __('seo-pro::messages.hourly'),
                                                'daily' => __('seo-pro::messages.daily'),
                                                'weekly' => __('seo-pro::messages.weekly'),
                                                'monthly' => __('seo-pro::messages.monthly'),
                                                'yearly' => __('seo-pro::messages.yearly'),
                                                'never' => __('seo-pro::messages.never'),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'search' => [
                    'display' => __('seo-pro::messages.search_engines'),
                    'sections' => [
                        [
                            'display' => __('seo-pro::fieldsets/defaults.search_section'),
                            'instructions' => __('seo-pro::fieldsets/defaults.search_section_instruct'),
                            'fields' => [
                                [
                                    'handle' => 'bing_verification',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.bing_verification'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.bing_verification_instruct'),
                                        'type' => 'text',
                                        'localizable' => true,
                                    ],
                                ],
                                [
                                    'handle' => 'google_verification',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.google_verification'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.google_verification_instruct'),
                                        'type' => 'text',
                                        'localizable' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
