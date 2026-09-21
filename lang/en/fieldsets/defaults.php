<?php

return [

    'meta_section' => 'Meta Data',
    'meta_section_instruct' => 'Every URL in your site should have a unique meta title and description.',

    'title' => 'Meta Title',
    'title_instruct' => 'Choose the field to use for your page titles',

    'description' => 'Meta Description',
    'description_instruct' => 'Choose the field to use for your meta descriptions',

    'site_name' => 'Site Name',
    'site_name_instruct' => 'Your site\'s name, added to meta titles for brand consistency.',

    'site_name_position' => 'Name Position',
    'site_name_position_instruct' => 'Show your site name before or after the page title. Or not at all.',

    'site_name_separator' => 'Separator',
    'site_name_separator_instruct' => 'Choose what appears between the page title and site name.',

    'canonical_url' => 'Canonical URL',
    'canonical_url_instruct' => 'Choose the field for your canonical URLs (usually `permalink`).',

    'json_ld_section' => 'JSON-LD',

    'json_ld_entity_section' => 'Entity Information',
    'json_ld_entity_section_instruct' => 'Details about the organization, person, or business this site represents. This schema is only output on the homepage, as [recommended by Google](https://developers.google.com/search/docs/appearance/structured-data/organization).',
    'json_ld_entity' => 'Entity',
    'json_ld_entity_instruct' => 'This helps search engines understand the structured data on your site and improves the likelihood of it appearing with rich search results.',
    'json_ld_entity_name' => 'Name',
    'json_ld_entity_name_instruct' => 'The name of the entity this site represents.',
    'json_ld_entity_alternate_name' => 'Alternate Name',
    'json_ld_entity_alternate_name_instruct' => 'An alternative name the entity is also known by.',
    'json_ld_entity_description' => 'Description',
    'json_ld_entity_description_instruct' => 'A short description of the entity.',
    'json_ld_entity_url' => 'URL',
    'json_ld_entity_url_instruct' => 'The canonical URL for the entity. Defaults to the homepage URL when left blank.',
    'json_ld_entity_logo' => 'Logo / Image',
    'json_ld_entity_logo_instruct' => 'The logo or image representing the entity.',
    'json_ld_entity_telephone' => 'Telephone',
    'json_ld_entity_telephone_instruct' => 'The primary contact telephone number.',
    'json_ld_entity_email' => 'Email',
    'json_ld_entity_email_instruct' => 'The primary contact email address.',
    'json_ld_entity_same_as' => 'Same As URLs',
    'json_ld_entity_same_as_add_row' => 'Add URL',
    'json_ld_entity_same_as_instruct' => 'URLs of social media profiles and other web presences that reference the entity.',
    'json_ld_entity_street_address' => 'Street Address',
    'json_ld_entity_street_address_instruct' => 'The street address of the entity that owns this site.',
    'json_ld_entity_locality' => 'Locality / City',
    'json_ld_entity_locality_instruct' => 'The locality (city) of the entity that owns this site.',
    'json_ld_entity_region' => 'Region / State / Province',
    'json_ld_entity_region_instruct' => 'The region, state or province of the entity that owns this site.',
    'json_ld_entity_postal_code' => 'Postal Code',
    'json_ld_entity_postal_code_instruct' => 'The postal code of the entity that owns this site.',
    'json_ld_entity_country' => 'Country',
    'json_ld_entity_country_instruct' => 'The country of the entity that owns this site. Recommended to be in 2-letter [ISO 3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1) format.',
    'json_ld_entity_latitude' => 'Latitude',
    'json_ld_entity_latitude_instruct' => 'You can find an addresses coordinates using [latlong.net](https://www.latlong.net/convert-address-to-lat-long.html).',
    'json_ld_entity_longitude' => 'Longitude',
    'json_ld_entity_longitude_instruct' => 'You can find an addresses coordinates using [latlong.net](https://www.latlong.net/convert-address-to-lat-long.html).',
    'json_ld_entity_price_range' => 'Price Range',
    'json_ld_entity_price_range_instruct' => 'The relative price range of the business.',
    'json_ld_entity_opening_hours' => 'Opening Hours',
    'json_ld_entity_opening_hours_instruct' => 'Leave a day blank to mark it as closed.',
    'json_ld_entity_ticker_symbol' => 'Ticker Symbol',
    'json_ld_entity_ticker_symbol_instruct' => 'The stock exchange ticker symbol, e.g. `NASDAQ:AAPL`.',

    'json_ld_custom_section' => 'Custom Schema',
    'json_ld_custom_section_instruct' => 'Add a custom JSON-LD schema that will be output on every page across your site.',
    'json_ld_schema' => 'Schema',
    'json_ld_schema_instruct' => 'Paste your custom schema objects here (`WebSite`, `SiteNavigationElement`, etc). Will be wrapped in the appropriate script tag.',

    'json_ld_breadcrumbs_section' => 'Breadcrumbs',
    'json_ld_breadcrumbs' => 'Breadcrumbs',
    'json_ld_breadcrumbs_instruct' => 'Enable breadcrumb structured data to show this page\'s location in your site hierarchy in search results. [Learn more](https://developers.google.com/search/docs/appearance/structured-data/breadcrumb)',

    'robots_section' => 'Robots',
    'robots_section_instruct' => 'Control how search engines crawl and index your pages.',

    'robots_indexing' => 'Indexing',
    'robots_indexing_instruct' => 'Control whether search engines can index this site.',

    'robots_following' => 'Link Following',
    'robots_following_instruct' => 'Control whether search engines can follow links for this site.',

    'robots_noarchive' => 'No Archive',
    'robots_noarchive_instruct' => 'Prevent search engines from showing cached links for this site.',

    'robots_noimageindex' => 'No Image Index',
    'robots_noimageindex_instruct' => 'Prevent search engines from indexing images on this site.',

    'robots_nosnippet' => 'No Snippet',
    'robots_nosnippet_instruct' => 'Prevent search engines from showing text snippets for this site.',

    'image_section' => 'Open Graph',
    'image_section_instruct' => 'We automatically generate most Open Graph fields from your meta data and site configuration.',

    'image' => 'Image',
    'image_instruct' => 'Choose a default image field to represent each URL when shared on social networks.',

    'og_type' => 'Open Graph Type',
    'og_type_instruct' => 'The type of content (eg. website, article). [Learn more](https://ogp.me/#types)',

    'og_title' => 'Open Graph Title',
    'og_title_instruct' => 'Choose the field for your Open Graph titles.',

    'social_section' => 'X (Twitter)',
    'social_section_instruct' => 'Choose the field for your X card titles and descriptions.',

    'twitter_handle' => 'Handle',
    'twitter_handle_instruct' => 'Enter the handle of your X profile.',

    'twitter_title' => 'Card Title',
    'twitter_title_instruct' => 'Choose the field for your X card titles.',

    'twitter_description' => 'Card Description',
    'twitter_description_instruct' => 'Choose the field or set a custom value for your default X card descriptions.',

    'sitemap_section' => 'Site Map',
    'sitemap_section_instruct' => 'Choose your sitemap settings.',

    'priority' => 'Priority',
    'priority_instruct' => 'Set the priority of this page in the sitemap. Valid values range from `0.0` to `1.0`.',

    'change_frequency' => 'Change Frequency',
    'change_frequency_instruct' => 'Set how often the page is likely to change.',

    'search_section' => 'Search Engines',
    'search_section_instruct' => "Verify your site with popular search engines to track how well they're crawling your site.",

    'bing_verification' => 'Bing Verification Code',
    'bing_verification_instruct' => 'Enter your verification code from [Bing Webmaster Tools](https://www.bing.com/toolbox/webmaster).',

    'google_verification' => 'Google Verification Code',
    'google_verification_instruct' => 'Enter your verification code from [Google Search Console](https://search.google.com/search-console).',

];
