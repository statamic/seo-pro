<?php

namespace Statamic\SeoPro;

class Fields
{
    use HasAssetField, GetsSectionDefaults;

    protected $data;
    protected $isContent;

    /**
     * Instantiate SEO fields config.
     *
     * @param mixed $data
     */
    public function __construct($data = null)
    {
        $this->data = $data;
        $this->isContent = ! is_null($data);
    }

    /**
     * Instantiate SEO fields config.
     *
     * @return static
     */
    public static function new($data = null)
    {
        return new static($data);
    }

    /**
     * SEO field config.
     *
     * @return array
     */
    public function getConfig()
    {
        $langFile = $this->isContent ? 'content' : 'sections';

        return [
            [
                'handle' => 'enabled',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.enabled"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.enabled_instruct"),
                    'type' => 'toggle',
                    'default' => true,
                ],
            ],
            [
                'handle' => 'title',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.title"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.title_instruct"),
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
                    'display' => __("seo-pro::fieldsets/{$langFile}.description"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.description_instruct"),
                    'placeholder' => $this->getPlaceholder('description'),
                    'type' => 'seo_pro_source',
                    'field' => [
                        'type' => 'textarea',
                    ],
                ],
            ],
            [
                'handle' => 'site_name',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.site_name"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.site_name_instruct"),
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
                    'display' => __("seo-pro::fieldsets/{$langFile}.site_name_position"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.site_name_position_instruct"),
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
                'handle' => 'site_name_separator',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.site_name_separator"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.site_name_separator_instruct"),
                    'placeholder' => $this->getPlaceholder('site_name_separator'),
                    'type' => 'seo_pro_source',
                    'from_field' => false,
                    'field' => [
                        'type' => 'text',
                    ],
                ],
            ],
            [
                'handle' => 'canonical_url',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.canonical_url"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.canonical_url_instruct"),
                    'placeholder' => $this->isContent ? $this->getPlaceholder('canonical_url') : false,
                    'type' => 'seo_pro_source',
                    'field' => $this->isContent ? ['type' => 'text'] : false,
                ],
            ],
            [
                'handle' => 'robots',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.robots"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.robots_instruct"),
                    'placeholder' => $this->getPlaceholder('robots'),
                    'type' => 'seo_pro_source',
                    'from_field' => false,
                    'disableable' => true,
                    'field' => [
                        'type' => 'select',
                        'create' => true,
                        'multiple' => true,
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
                    'display' => __("seo-pro::fieldsets/{$langFile}.image"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.image_instruct"),
                    'placeholder' => $this->getPlaceholder('image'),
                    'type' => 'seo_pro_source',
                    'allowed_fieldtypes' => [
                        'assets',
                    ],
                    'field' => static::getAssetFieldConfig(),
                ],
            ],
            [
                'handle' => 'twitter_handle',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.twitter_handle"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.twitter_handle_instruct"),
                    'type' => 'seo_pro_source',
                    'field' => [
                        'type' => 'text',
                    ],
                ],
            ],
            [
                'handle' => 'sitemap',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.sitemap"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.sitemap_instruct"),
                    'placeholder' => $this->getPlaceholder('sitemap'),
                    'type' => 'seo_pro_source',
                    'disableable' => true,
                    'field' => false,
                    'from_field' => false,
                ],
            ],
            [
                'handle' => 'priority',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.priority"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.priority_instruct"),
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
                    'display' => __("seo-pro::fieldsets/{$langFile}.change_frequency"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.change_frequency_instruct"),
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
        $cascade = (new Cascade)->with(SiteDefaults::load()->all());

        if ($this->data) {
            $cascade = $cascade
                ->with($this->getSectionDefaults($this->data))
                ->with($this->data->value('seo', []))
                ->withCurrent($this->data);
        }

        $placeholder = $cascade->value($handle);

        if (is_array($placeholder)) {
            return collect($placeholder)->implode(', ');
        }

        return $placeholder;
    }
}
