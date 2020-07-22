<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\Blueprint;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\HasAssetField;
use Statamic\SeoPro\SiteDefaults;
use Statamic\Support\Arr;

class SiteDefaultsController extends CpController
{
    use HasAssetField;

    public function edit()
    {
        $blueprint = $this->blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues(SiteDefaults::load()->all())
            ->preProcess();

        return view('seo-pro::edit', [
            'title' => 'Site Defaults',
            'action' => cp_route('seo-pro.site-defaults.update'),
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
        ]);
    }

    public function update(Request $request)
    {
        $blueprint = $this->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = Arr::removeNullValues($fields->process()->values()->all());

        SiteDefaults::load($values)->save();
    }

    protected function blueprint()
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
                                    'type' => 'text',
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
                                    'assets', // TODO: 'from field' dropdown should only suggest assets fields
                                ],
                                'field' => $this->getAssetFieldConfig(),
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
