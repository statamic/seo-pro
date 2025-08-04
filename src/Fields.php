<?php

namespace Statamic\SeoPro;

use Statamic\Assets\Asset;
use Statamic\Facades\Blink;
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
                'handle' => 'enabled',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.enabled"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.enabled_instruct"),
                    'type' => 'toggle',
                    'default' => true,
                    'localizable' => true,
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
                    'localizable' => true,
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
                    'localizable' => true,
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
                    'localizable' => true,
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
                    'localizable' => true,
                    'field' => [
                        'type' => 'select',
                        'options' => [
                            'after' => __('seo-pro::messages.after'),
                            'before' => __('seo-pro::messages.before'),
                            'none' => __('seo-pro::messages.disable'),
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
                    'localizable' => true,
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
                    'localizable' => true,
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
                    'localizable' => true,
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
                    'localizable' => true,
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
                    'localizable' => true,
                    'field' => [
                        'type' => 'text',
                    ],
                ],
            ],
            [
                'handle' => 'og_title',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.og_title"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.og_title_instruct"),
                    'placeholder' => $this->getPlaceholder('og_title'),
                    'type' => 'seo_pro_source',
                    'localizable' => true,
                    'field' => [
                        'type' => 'text',
                    ],
                ],
            ],
            [
                'handle' => 'og_description',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.og_description"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.og_description_instruct"),
                    'placeholder' => $this->getPlaceholder('og_description'),
                    'type' => 'seo_pro_source',
                    'localizable' => true,
                    'field' => [
                        'type' => 'textarea',
                    ],
                ],
            ],
            [
                'handle' => 'twitter_title',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.twitter_title"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.twitter_title_instruct"),
                    'placeholder' => $this->getPlaceholder('twitter_title'),
                    'type' => 'seo_pro_source',
                    'localizable' => true,
                    'field' => [
                        'type' => 'text',
                    ],
                ],
            ],
            [
                'handle' => 'twitter_description',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.twitter_description"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.twitter_description_instruct"),
                    'placeholder' => $this->getPlaceholder('twitter_description'),
                    'type' => 'seo_pro_source',
                    'localizable' => true,
                    'field' => [
                        'type' => 'textarea',
                    ],
                ],
            ],
            [
                'handle' => 'author',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.author"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.author_instruct"),
                    'placeholder' => $this->getPlaceholder('author'),
                    'type' => 'seo_pro_source',
                    'localizable' => true,
                    'field' => [
                        'type' => 'text',
                    ],
                ],
            ],
            [
                'handle' => 'published_date',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.published_date"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.published_date_instruct"),
                    'placeholder' => $this->getPlaceholder('published_date'),
                    'type' => 'seo_pro_source',
                    'from_field' => 'date',
                    'localizable' => true,
                    'field' => false,
                ],
            ],
            [
                'handle' => 'updated_date',
                'field' => [
                    'display' => __("seo-pro::fieldsets/{$langFile}.updated_date"),
                    'instructions' => __("seo-pro::fieldsets/{$langFile}.updated_date_instruct"),
                    'placeholder' => $this->getPlaceholder('updated_date'),
                    'type' => 'seo_pro_source',
                    'from_field' => false,
                    'localizable' => true,
                    'field' => false,
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
                    'localizable' => true,
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
            $cascade = (new Cascade)->with(SiteDefaults::load()->all());

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
