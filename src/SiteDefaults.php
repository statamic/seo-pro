<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Collection;
use Statamic\Facades\Blueprint;
use Statamic\Facades\File;
use Statamic\Facades\YAML;

class SiteDefaults extends Collection
{
    use HasAssetField;

    /**
     * Load site defaults collection.
     *
     * @param array|Collection|null $items
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
     * @param array|Collection|null $items
     * @return static
     */
    public static function load($items = null)
    {
        return new static($items);
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

        $defaultValues = static::blueprint()
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
    }

    /**
     * Get site defaults from yaml.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return collect(YAML::file(__DIR__.'/../content/seo.yaml')->parse())
            ->merge(YAML::file($this->path())->parse())
            ->all();
    }

    /**
     * Get site defaults yaml path.
     *
     * @return string
     */
    protected function path()
    {
        return base_path('content/seo.yaml');
    }

    /**
     * Get site defaults blueprint.
     *
     * @return \Statamic\Fields\Blueprint
     */
    public static function blueprint()
    {
        return Blueprint::make()->setContents([
            'sections' => [
                'meta' => [
                    'display' => __('Meta'),
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
                            ],
                        ],
                        [
                            'handle' => 'site_name_position',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.site_name_position'),
                                'instructions' => __('seo-pro::fieldsets/defaults.site_name_position_instruct'),
                                'type' => 'select',
                                'options' => [
                                    'after' => 'After',
                                    'before' => 'Before',
                                    'none' => 'Disable',
                                ],
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'site_name_separator',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.site_name_separator'),
                                'instructions' => __('seo-pro::fieldsets/defaults.site_name_separator_instruct'),
                                'type' => 'text',
                                'width' => 50,
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
                            ],
                        ],
                    ],
                ],
                'image' => [
                    'display' => __('Image'),
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
                                'field' => static::getAssetFieldConfig(),
                            ],
                        ],
                    ],
                ],
                'social' => [
                    'display' => __('Social'),
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
                            ],
                        ],
                    ],
                ],
                'sitemap' => [
                    'display' => __('Sitemap'),
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
                                'field' => [
                                    'type' => 'select',
                                    'options' => [
                                        'hourly' => 'Hourly',
                                        'daily' => 'Daily',
                                        'weekly' => 'Weekly',
                                        'monthly' => 'Monthly',
                                        'yearly' => 'Yearly',
                                        'never' => 'Never',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'search' => [
                    'display' => __('Search Engines'),
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
                            ],
                        ],
                        [
                            'handle' => 'google_verification',
                            'field' => [
                                'display' => __('seo-pro::fieldsets/defaults.google_verification'),
                                'instructions' => __('seo-pro::fieldsets/defaults.google_verification_instruct'),
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
