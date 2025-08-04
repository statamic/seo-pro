<?php

return [

    'enabled' => 'Enabled',
    'enabled_instruct' => 'Disabling this item will exclude it from reports and the sitemap, and prevent anything from being rendered through the template tag.',

    'title' => 'Meta Title',
    'title_instruct' => 'Every URL in your site should have a unique Meta Title, ideally less than 60 characters long.',

    'description' => 'Meta Description',
    'description_instruct' => 'Every URL in your site should have a unique Meta Description, ideally less than 160 characters long.',

    'site_name' => 'Site Name',
    'site_name_instruct' => 'Optionally disable the site name for this page.',

    'site_name_position' => 'Site Name Position',
    'site_name_position_instruct' => 'Optionally adjust the position for this page.',

    'site_name_separator' => 'Site Name Separator',
    'site_name_separator_instruct' => 'Optionally adjust the separator for this page.',

    'canonical_url' => 'Canonical URL',
    'canonical_url_instruct' => 'Every URL in your site should have a unique canonical URL.',

    'robots' => 'Robots',
    'robots_instruct' => 'Pick options for the robots meta tag. noindex prevents the page being indexed by search engines. nofollow prevents search engines from crawling links.',

    'image' => 'Social Image',
    'image_instruct' => 'This image is used as a social network preview image.',

    'twitter_handle' => 'Twitter Handle',
    'twitter_handle_instruct' => 'Optionally override the twitter handle for this page.',

    'og_title' => 'Open Graph Title',
    'og_title_instruct' => 'Set a custom title for Open Graph (Facebook, LinkedIn, etc). Defaults to Meta Title.',

    'og_description' => 'Open Graph Description (Optional)',
    'og_description_instruct' => 'Set a custom description for Open Graph (Facebook, LinkedIn, etc). This field is optional - if left blank, it will use the Meta Description.',

    'twitter_title' => 'Twitter Title',
    'twitter_title_instruct' => 'Set a custom title for Twitter Cards. Defaults to Meta Title.',

    'twitter_description' => 'Twitter Description (Optional)',
    'twitter_description_instruct' => 'Set a custom description for Twitter Cards. This field is optional - if left blank, it will use the Meta Description.',

    'author' => 'Author',
    'author_instruct' => 'Set a custom author name. Defaults to the entry author.',

    'published_date' => 'Published Date',
    'published_date_instruct' => 'Maps to the entry date field. Used for article:published_time meta tag.',

    'updated_date' => 'Updated Date',
    'updated_date_instruct' => 'Automatically uses the last modified date. Used for article:modified_time meta tag.',

    'sitemap' => 'Sitemap',
    'sitemap_instruct' => 'If disabled, this item will not appear in the sitemap.',

    'priority' => 'Sitemap: Priority',
    'priority_instruct' => 'The priority of this URL relative to other URLs on your site. Valid values range from `0.0` to `1.0`.',

    'change_frequency' => 'Sitemap: Change Frequency',
    'change_frequency_instruct' => 'A hint to search engines on how frequently the page is likely to change.',

];
