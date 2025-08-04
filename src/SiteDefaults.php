<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Facades\Blueprint;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Events\SeoProSiteDefaultsSaved;

class SiteDefaults extends Collection
{
    use HasAssetField;

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
     * Get augmented.
     *
     * @return array
     */
    public function augmented()
    {
        $contentValues = Blueprint::make()
            ->setContents(['fields' => Fields::new()->getConfig()])
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
     * Save site defaults collection to yaml.
     */
    public function save()
    {
        File::put($this->path(), YAML::dump($this->items));

        SeoProSiteDefaultsSaved::dispatch($this);

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
            return collect(YAML::file(__DIR__.'/../content/seo.yaml')->parse())
                ->merge(YAML::file($this->path())->parse())
                ->all();
        });
    }

    /**
     * Get site defaults yaml path.
     *
     * @return string
     */
    protected function path()
    {
        return config('statamic.seo-pro.site_defaults.path');
    }

    /**
     * Get site defaults blueprint.
     *
     * @return \Statamic\Fields\Blueprint
     */
    public function blueprint()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'meta' => [
                    'display' => __('seo-pro::messages.meta'),
                    'fields' => [
                        [
                            'handle' => 'meta_section',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.meta_section'),
                                'instructions' => __('seo-pro::fieldsets/defaults.meta_section_instruct'),
                                'type' => 'section',
                            ],
                        ],
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
                        [
                            'handle' => 'robots',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.robots'),
                                'instructions' => __('seo-pro::fieldsets/defaults.robots_instruct'),
                                'type' => 'select',
                                'multiple' => true,
                                'options' => [
                                    'noindex',
                                    'nofollow',
                                ],
                                'localizable' => true,
                            ],
                        ],
                    ],
                ],
                'image' => [
                    'display' => __('seo-pro::messages.image'),
                    'fields' => [
                        [
                            'handle' => 'image_section',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.image_section'),
                                'instructions' => __('seo-pro::fieldsets/defaults.image_section_instruct'),
                                'type' => 'section',
                            ],
                        ],
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
                        [
                            'handle' => 'og_title',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.og_title'),
                                'instructions' => __('seo-pro::fieldsets/defaults.og_title_instruct'),
                                'type' => 'text',
                                'localizable' => true,
                            ],
                        ],
                        [
                            'handle' => 'og_description',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.og_description'),
                                'instructions' => __('seo-pro::fieldsets/defaults.og_description_instruct'),
                                'type' => 'textarea',
                                'localizable' => true,
                            ],
                        ],
                    ],
                ],
                'social' => [
                    'display' => __('seo-pro::messages.social'),
                    'fields' => [
                        [
                            'handle' => 'social_section',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.social_section'),
                                'instructions' => __('seo-pro::fieldsets/defaults.social_section_instruct'),
                                'type' => 'section',
                            ],
                        ],
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
                                'type' => 'text',
                                'localizable' => true,
                            ],
                        ],
                        [
                            'handle' => 'twitter_description',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.twitter_description'),
                                'instructions' => __('seo-pro::fieldsets/defaults.twitter_description_instruct'),
                                'type' => 'textarea',
                                'localizable' => true,
                            ],
                        ],
                    ],
                ],
                'sitemap' => [
                    'display' => __('seo-pro::messages.sitemap'),
                    'fields' => [
                        [
                            'handle' => 'sitemap_section',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.sitemap_section'),
                                'instructions' => __('seo-pro::fieldsets/defaults.sitemap_section_instruct'),
                                'type' => 'section',
                            ],
                        ],
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
                'search' => [
                    'display' => __('seo-pro::messages.search_engines'),
                    'fields' => [
                        [
                            'handle' => 'search_section',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.search_section'),
                                'instructions' => __('seo-pro::fieldsets/defaults.search_section_instruct'),
                                'type' => 'section',
                            ],
                        ],
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
        ]);
    }
}
