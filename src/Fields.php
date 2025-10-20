<?php

namespace Statamic\SeoPro;

use Statamic\Assets\Asset;
use Statamic\Facades\Blink;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;
use Statamic\Statamic;

class Fields
{
    use GetsSectionDefaults, HasAssetField;

    protected $data;
    protected $isContent;

    /**
     * Instantiate SEO fields config.
     *
     * @param  mixed  $data
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
                'fields' => [
                    [
                        'handle' => 'enabled',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.enabled"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.enabled_instruct"),
                            'type' => 'toggle',
                            'default' => true,
                            'localizable' => true,
                        ],
                    ],
                ],
            ],
            [
                'display' => __('seo-pro::fieldsets/defaults.meta_section'),
                'instructions' => __('seo-pro::fieldsets/defaults.meta_section_instruct'),
                'fields' => [
                    [
                        'handle' => 'title',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.title"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.title_instruct"),
                            'placeholder' => $this->getPlaceholder('title'),
                            'type' => 'seo_pro_source',
                            'disableable' => true,
                            'localizable' => true,
                            'field' => [
                                'type' => 'text',
                                'character_limit' => 60,
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'description',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.description"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.description_instruct"),
                            'placeholder' => $this->getPlaceholder('description'),
                            'type' => 'seo_pro_source',
                            'localizable' => true,
                            'field' => [
                                'type' => 'textarea',
                                'character_limit' => 160,
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
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
                            'localizable' => true,
                            'field' => false,
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
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
                            'localizable' => true,
                            'field' => [
                                'type' => 'select',
                                'options' => [
                                    'after' => __('seo-pro::messages.after'),
                                    'before' => __('seo-pro::messages.before'),
                                    'none' => __('seo-pro::messages.disable'),
                                ],
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
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
                            'localizable' => true,
                            'field' => [
                                'type' => 'text',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'canonical_url',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.canonical_url"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.canonical_url_instruct"),
                            'placeholder' => $this->isContent ? $this->getPlaceholder('canonical_url') : false,
                            'type' => 'seo_pro_source',
                            'localizable' => true,
                            'field' => $this->isContent ? ['type' => 'text'] : false,
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                ],
            ],
            [
                'display' => __('seo-pro::fieldsets/defaults.robots_section'),
                'instructions' => __('seo-pro::fieldsets/defaults.robots_section_instruct'),
                'fields' => [
                    [
                        'handle' => 'robots',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.robots"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.robots_instruct"),
                            'type' => 'seo_pro_source',
                            'from_field' => false,
                            'disableable' => true,
                            'localizable' => true,
                            'field' => [
                                'type' => 'checkboxes',
                                'options' => [
                                    'noindex' => 'Noindex',
                                    'nofollow' => 'Nofollow',
                                    'noarchive' => 'Noarchive',
                                    'noimageindex' => 'Noimageindex',
                                    'nosnippet' => 'Nosnippet',
                                ],
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'robots_indexing',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.robots_indexing"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.robots_indexing_instruct"),
                            'placeholder' => $this->getPlaceholder('robots_indexing'),
                            'type' => 'seo_pro_source',
                            'from_field' => false,
                            'disableable' => true,
                            'localizable' => true,
                            'field' => [
                                'type' => 'button_group',
                                'options' => [
                                    'index' => 'Index',
                                    'noindex' => 'Noindex',
                                ],
                                'default' => 'index',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'robots_following',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.robots_following"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.robots_following_instruct"),
                            'placeholder' => $this->getPlaceholder('robots_following'),
                            'type' => 'seo_pro_source',
                            'from_field' => false,
                            'disableable' => true,
                            'localizable' => true,
                            'field' => [
                                'type' => 'button_group',
                                'options' => [
                                    'follow' => 'Follow',
                                    'nofollow' => 'Nofollow',
                                ],
                                'default' => 'follow',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'robots_noarchive',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.robots_noarchive"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.robots_noarchive_instruct"),
                            'placeholder' => $this->getPlaceholder('robots_noarchive'),
                            'type' => 'seo_pro_source',
                            'from_field' => false,
                            'disableable' => true,
                            'localizable' => true,
                            'field' => [
                                'type' => 'toggle',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'robots_noimageindex',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.robots_noimageindex"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.robots_noimageindex_instruct"),
                            'placeholder' => $this->getPlaceholder('robots_noimageindex'),
                            'type' => 'seo_pro_source',
                            'from_field' => false,
                            'disableable' => true,
                            'localizable' => true,
                            'field' => [
                                'type' => 'toggle',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'robots_nosnippet',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.robots_nosnippet"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.robots_nosnippet_instruct"),
                            'placeholder' => $this->getPlaceholder('robots_nosnippet'),
                            'type' => 'seo_pro_source',
                            'from_field' => false,
                            'disableable' => true,
                            'localizable' => true,
                            'field' => [
                                'type' => 'toggle',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                ],
            ],
            [
                'display' => __('seo-pro::fieldsets/defaults.image_section'),
                'instructions' => __('seo-pro::fieldsets/defaults.image_section_instruct'),
                'fields' => [
                    [
                        'handle' => 'og_title',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.og_title"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.og_title_instruct"),
                            'type' => 'seo_pro_source',
                            'inherit' => true,
                            'localizable' => true,
                            'field' => [
                                'type' => 'text',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'image',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.image"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.image_instruct"),
                            'placeholder' => $this->getPlaceholder('image'),
                            'type' => 'seo_pro_source',
                            'localizable' => true,
                            'allowed_fieldtypes' => [
                                'assets',
                            ],
                            'field' => static::getAssetFieldConfig(),
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                ],
            ],
            [
                'display' => __('seo-pro::fieldsets/defaults.social_section'),
                'instructions' => __('seo-pro::fieldsets/defaults.social_section_instruct'),
                'fields' => [
                    [
                        'handle' => 'twitter_handle',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.twitter_handle"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.twitter_handle_instruct"),
                            'type' => 'seo_pro_source',
                            'localizable' => true,
                            'field' => [
                                'type' => 'text',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'twitter_title',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.twitter_title"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.twitter_title_instruct"),
                            'type' => 'seo_pro_source',
                            'inherit' => true,
                            'localizable' => true,
                            'field' => [
                                'type' => 'text',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'twitter_description',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.twitter_description"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.twitter_description_instruct"),
                            'type' => 'seo_pro_source',
                            'inherit' => true,
                            'localizable' => true,
                            'field' => [
                                'type' => 'textarea',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                ],
            ],
            [
                'display' => __('seo-pro::fieldsets/defaults.sitemap_section'),
                'instructions' => __('seo-pro::fieldsets/defaults.sitemap_section_instruct'),
                'fields' => [
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
                            'localizable' => true,
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'priority',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.priority"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.priority_instruct"),
                            'placeholder' => $this->getPlaceholder('priority'),
                            'type' => 'seo_pro_source',
                            'localizable' => true,
                            'field' => [
                                'type' => 'text',
                            ],
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                    [
                        'handle' => 'change_frequency',
                        'field' => [
                            'display' => __("seo-pro::fieldsets/{$langFile}.change_frequency"),
                            'instructions' => __("seo-pro::fieldsets/{$langFile}.change_frequency_instruct"),
                            'placeholder' => $this->getPlaceholder('change_frequency'),
                            'type' => 'seo_pro_source',
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
                            'always_save' => true,
                            'unless' => ['enabled' => 'equals false'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get inherited value from SEO cascade for use as placeholder.
     *
     * @param  string  $handle
     * @return mixed
     */
    protected function getPlaceholder($handle)
    {
        if (! Statamic::isCpRoute()) {
            return null;
        }

        $cascade = Blink::once('seo-pro::placeholder.cascade', function () {
            $cascade = (new Cascade)->with(SiteDefaults::get()->first()->all());

            if ($this->data) {
                $cascade = $cascade
                    ->with($this->getSectionDefaults($this->data))
                    ->with($this->data->value('seo', []))
                    ->withCurrent($this->data);
            }

            return $cascade;
        });

        $placeholder = $cascade->value($handle);

        if (is_array($placeholder)) {
            return collect($placeholder)->implode(', ');
        } elseif ($placeholder instanceof Asset) {
            return $placeholder->path();
        }

        return $placeholder;
    }
}
