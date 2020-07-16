<?php

namespace Statamic\SeoPro;

use Statamic\Events\BlueprintFound;
use Statamic\Support\Arr;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Statamic\Fields\FieldTransformer;
use Statamic\Statamic;

class Blueprint
{
    protected $blueprint;
    protected $data;

    /**
     * Instantiate blueprint found event handler.
     *
     * @param BlueprintFound $event
     */
    public function __construct(BlueprintFound $event)
    {
        $this->blueprint = $event->blueprint;
        $this->data = $event->data;
    }

    /**
     * Instantiate blueprint found event handler.
     *
     * @param BlueprintFound $event
     * @return static
     */
    public static function on(BlueprintFound $event)
    {
        return new static($event);
    }

    /**
     * Add SEO section and fields to blueprint.
     *
     * @param BlueprintFound $event
     */
    public function addSeoFields()
    {
        $this->blueprint->ensureFieldInSection('seo', $this->seoField(), __('SEO'));
    }

    /**
     * SEO field config.
     *
     * @return array
     */
    protected function seoField()
    {
        return [
            'type' => 'seo_pro',
            'fields' => [
                [
                    'handle' => 'enabled',
                    'field' => [
                        'display' => __('seo-pro::fieldsets/content.enabled'),
                        'instructions' => __('seo-pro::fieldsets/content.enabled_instruct'),
                        'type' => 'toggle',
                        'default' => true,
                    ],
                ],
                [
                    'handle' => 'title',
                    'field' => [
                        'display' => __('seo-pro::fieldsets/content.title'),
                        'instructions' => __('seo-pro::fieldsets/content.title_instruct'),
                        'placeholder' => $this->getPlaceholder('title'),
                        'type' => 'seo_pro_source',
                        'disableable' => true,
                        'field' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                [
                    'handle' => 'description',
                    'field' => [
                        'display' => __('seo-pro::fieldsets/content.description'),
                        'instructions' => __('seo-pro::fieldsets/content.description_instruct'),
                        'placeholder' => $this->getPlaceholder('description'),
                        'type' => 'seo_pro_source',
                        'field' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                [
                    'handle' => 'site_name',
                    'field' => [
                        'display' => __('seo-pro::fieldsets/content.site_name'),
                        'instructions' => __('seo-pro::fieldsets/content.site_name_instruct'),
                        'placeholder' => $this->getPlaceholder('site_name'),
                        'type' => 'seo_pro_source',
                        'from_field' => false,
                        'disableable' => true,
                        'field' => false,
                    ],
                ],
                [
                    'handle' => 'site_name_position',
                    'field' => [
                        'display' => __('seo-pro::fieldsets/content.site_name'),
                        'instructions' => __('seo-pro::fieldsets/content.site_name_instruct'),
                        'placeholder' => $this->getPlaceholder('site_name_position'),
                        'type' => 'seo_pro_source',
                        'from_field' => false,
                        'field' => [
                            'type' => 'select',
                            'options' => [
                                'after' => 'After',
                                'before' => 'Before',
                                'none' => 'Disable',
                            ],
                        ],
                    ],
                ],
                [
                    'handle' => 'robots',
                    'field' => [
                        'display' => __('seo-pro::fieldsets/content.robots'),
                        'instructions' => __('seo-pro::fieldsets/content.robots_instruct'),
                        'placeholder' => $this->getPlaceholder('robots'),
                        'type' => 'seo_pro_source',
                        'from_field' => false,
                        'disableable' => true,
                        'field' => [
                            'type' => 'select',
                            'create' => true,
                            'options' => [
                                'noindex',
                                'nofollow',
                            ],
                        ],
                    ],
                ],
                [
                    'handle' => 'image',
                    'field' => [
                        'display' => __('seo-pro::fieldsets/content.image'),
                        'instructions' => __('seo-pro::fieldsets/content.image_instruct'),
                        'placeholder' => $this->getPlaceholder('image'),
                        'type' => 'seo_pro_source',
                        'allowed_fieldtypes' => [
                            'assets', // ???
                        ],
                        'field' => $this->getAssetFieldConfig(),
                    ],
                ],
                [
                    'handle' => 'sitemap',
                    'field' => [
                        'display' => __('seo-pro::fieldsets/content.sitemap'),
                        'instructions' => __('seo-pro::fieldsets/content.sitemap_instruct'),
                        'placeholder' => $this->getPlaceholder('sitemap'),
                        'type' => 'seo_pro_source',
                        'disableable' => true,
                        'field' => false,
                    ],
                ],
                [
                    'handle' => 'priority',
                    'field' => [
                        'display' => __('seo-pro::fieldsets/content.priority'),
                        'instructions' => __('seo-pro::fieldsets/content.priority_instruct'),
                        'placeholder' => $this->getPlaceholder('priority'),
                        'type' => 'seo_pro_source',
                        'field' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                [
                    'handle' => 'change_frequency',
                    'field' => [
                        'display' => __('seo-pro::fieldsets/content.change_frequency'),
                        'instructions' => __('seo-pro::fieldsets/content.change_frequency_instruct'),
                        'placeholder' => $this->getPlaceholder('change_frequency'),
                        'type' => 'seo_pro_source',
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
        ];
    }

    /**
     * Get inherited value from SEO cascade for use as placeholder.
     *
     * @param string $handle
     * @return mixed
     */
    protected function getPlaceholder($handle)
    {
        if (! $this->data) {
            return null;
        }

        return (new Cascade)
            ->with(config('statamic.seo-pro.defaults'))
            // ->with($this->getSectionDefaults($this->data))
            ->with($this->data->value('seo', []))
            ->withCurrent($this->data)
            ->value($handle);
    }

    /**
     * Get asset field config.
     j
     * @return array
     */
    protected function getAssetFieldConfig()
    {
        if (! $container = config('statamic.seo-pro.assets.container')) {
            return false;
        }

        return [
            'type' => 'assets',
            'container' => $container,
            'max_files' => 1,
        ];
    }
}
