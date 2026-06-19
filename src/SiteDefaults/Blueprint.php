<?php

namespace Statamic\SeoPro\SiteDefaults;

use Statamic\Facades\Site;
use Statamic\SeoPro\Fieldtypes\Rules\ValidJsonLd;
use Statamic\SeoPro\HasAssetField;

class Blueprint
{
    use HasAssetField;

    public static function get(): \Statamic\Fields\Blueprint
    {
        return \Statamic\Facades\Blueprint::make()->setContents([
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
                'json-ld' => [
                    'display' => __('seo-pro::fieldsets/defaults.json_ld_section'),
                    'sections' => [
                        [
                            'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_section'),
                            'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_section_instruct'),
                            'fields' => [
                                [
                                    'handle' => 'json_ld_entity',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_instruct'),
                                        'type' => 'select',
                                        'localizable' => true,
                                        'options' => [
                                            'organization' => __('seo-pro::messages.organization'),
                                            'person' => __('seo-pro::messages.person'),
                                            'local_business' => __('seo-pro::messages.local_business'),
                                            'corporation' => __('seo-pro::messages.corporation'),
                                            'disabled' => __('seo-pro::messages.disabled'),
                                        ],
                                        'default' => 'organization',
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_name',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_name'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_name_instruct'),
                                        'type' => 'text',
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_alternate_name',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_alternate_name'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_alternate_name_instruct'),
                                        'type' => 'text',
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_description',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_description'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_description_instruct'),
                                        'type' => 'textarea',
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_url',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_url'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_url_instruct'),
                                        'type' => 'text',
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_logo',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_logo'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_logo_instruct'),
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                        ...static::getAssetFieldConfig(),
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_telephone',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_telephone'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_telephone_instruct'),
                                        'type' => 'text',
                                        'width' => 50,
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_email',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_email'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_email_instruct'),
                                        'type' => 'text',
                                        'width' => 50,
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_same_as',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_same_as'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_same_as_instruct'),
                                        'add_row' => __('seo-pro::fieldsets/defaults.json_ld_entity_same_as_add_row'),
                                        'type' => 'list',
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_street_address',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_street_address'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_street_address_instruct'),
                                        'type' => 'text',
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_locality',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_locality'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_locality_instruct'),
                                        'type' => 'text',
                                        'width' => 50,
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_region',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_region'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_region_instruct'),
                                        'type' => 'text',
                                        'width' => 50,
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_postal_code',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_postal_code'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_postal_code_instruct'),
                                        'type' => 'text',
                                        'width' => 50,
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_country',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_country'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_country_instruct'),
                                        'type' => 'text',
                                        'width' => 50,
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_latitude',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_latitude'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_latitude_instruct'),
                                        'type' => 'text',
                                        'width' => 50,
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_longitude',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_longitude'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_longitude_instruct'),
                                        'type' => 'text',
                                        'width' => 50,
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'isnt disabled'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_price_range',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_price_range'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_price_range_instruct'),
                                        'type' => 'select',
                                        'clearable' => true,
                                        'options' => [
                                            '$' => '$',
                                            '$$' => '$$',
                                            '$$$' => '$$$',
                                            '$$$$' => '$$$$',
                                        ],
                                        'width' => 50,
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'equals local_business'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_opening_hours',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_opening_hours'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_opening_hours_instruct'),
                                        'type' => 'seo_pro_opening_hours',
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'equals local_business'],
                                    ],
                                ],
                                [
                                    'handle' => 'json_ld_entity_ticker_symbol',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_entity_ticker_symbol'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_entity_ticker_symbol_instruct'),
                                        'type' => 'text',
                                        'width' => 50,
                                        'localizable' => true,
                                        'if' => ['json_ld_entity' => 'equals corporation'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'display' => __('seo-pro::fieldsets/defaults.json_ld_custom_section'),
                            'instructions' => __('seo-pro::fieldsets/defaults.json_ld_custom_section_instruct'),
                            'fields' => [
                                [
                                    'handle' => 'json_ld_schema',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_schema'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_schema_instruct'),
                                        'type' => 'code',
                                        'mode' => 'javascript',
                                        'mode_selectable' => false,
                                        'show_mode_label' => false,
                                        'localizable' => true,
                                        'full_width_setting' => true,
                                        'fullscreen' => false,
                                        'validate' => [
                                            new ValidJsonLd,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'display' => __('seo-pro::fieldsets/defaults.json_ld_breadcrumbs_section'),
                            'fields' => [
                                [
                                    'handle' => 'json_ld_breadcrumbs',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.json_ld_breadcrumbs'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.json_ld_breadcrumbs_instruct'),
                                        'type' => 'toggle',
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
                                    'handle' => 'og_type',
                                    'field' => [
                                        'display' => __('seo-pro::fieldsets/defaults.og_type'),
                                        'instructions' => __('seo-pro::fieldsets/defaults.og_type_instruct'),
                                        'type' => 'text',
                                        'localizable' => true,
                                        'default' => 'website',
                                    ],
                                ],
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
