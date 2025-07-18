## Installation

1. Install SEO Pro with Composer:

    ```
    composer require statamic/seo-pro
    ```

2. Add the Antlers tag or Blade directive somewhere between your `<head>` tags.
    - Antlers: `{{ seo_pro:meta }}`
    - Blade: `@seo_pro('meta')`

## Usage

SEO settings will cascade down from the global defaults, to the collection/taxonomy level, and finally to the entry/term level.

Empty meta tags will not be rendered, which allows you to optionally set your own tags with other means if you so choose.

### Site Defaults

Head to `Tools > SEO Pro > Site Defaults` and configure your default settings. The defaults will be used if you haven't set anything more specific at the collection or entry level.

> Values configured here will be saved into `content/seo.yaml`.

You may choose to pull data from other fields, enter hardcoded strings, or use Antlers templating. See [File Usage](#file-usage) for more details.

### Section Defaults

Each section may be configured independently at the Collection / Taxonomy level. Head to `Tools > SEO Pro > Section Defaults` to configure default settings at this level. You may opt to inherit values from the defaults and tweak as necessary.

> Values configured here will be saved into the `seo` array (within `inject`) in the respective section's yaml config.

You may disable a section by toggling the "Enabled" field when editing a section, or set `seo: false` within the `inject` array in that section's yaml config. Disabling a section will prevent it's items from being included in reports, the sitemap, and prevent the template tag from rendering anything.

### Entries and Terms

It's better to configure your collections and taxonomies to dynamically pull from fields. However, an SEO tab will be added to each item's publish page and you are free to override any values there.

> Values configured here will be saved into the `seo` array in the item's front-matter.

### Assets

If you wish to use assets in your meta, you can [publish the SEO Pro config](#advanced-configuration) and specify an asset container, as well as the glide preset to be used.

> You may disable the glide preset altogether by setting `'open_graph_preset' => false,` in your config.

### Custom Statamic Routes

In the case that you're loading a custom [Statamic Route](https://statamic.dev/routing#statamic-routes), you can pass SEO meta directly into the route data param. This allows you to define custom meta on a route-by-route basis in situations without a proper collection entry.

```php
Route::statamic('search', 'search/index', [
    'title' => 'Search',
    'description' => 'Comprehensive Site Search.',
    // ...
]);
```


## File Usage

For advanced devs, you may bypass the CP and configure your SEO settings through files. There are 3 sorts of values you may save.

### Custom Hardcoded Strings

```yaml
title: "A hardcoded string"
```

### Field References

Prefix a field name with `@seo:` to have that field's value referenced automatically.

A field in a specific fieldset may be specified (this is how the CP will save them). The fieldset is completely optional and currently provides no additional benefit.

```yaml
title: "@seo:title"
title: "@seo:post/title"  # with optional fieldset
```

### Antlers Templating

You may use Statamic Antlers templating in your strings. When doing this, the addon will not apply any automatic parsing rules (limiting the length of the description, for example).

```yaml
description: "{{ content | strip_tags | truncate(250, '...') }}"
```

## Reports

You may generate an SEO report that checks all the pages of your site against a number of tests. The tests include mandatory items like title tag uniqueness, or suggested items like URLs being no more than 3 segments. Failing a mandatory item will result in a fail where failing a suggested item will result in a warning.

Reports will stick around until deleted, so you are free to compare reports to see how you are progressing.

You may generate a report through the Control Panel, or by running `php please seo-pro:generate-report`.

### Queuing Reports

Depending on the size of your site, generating a report may take a while. To prevent request timeouts, you may enable queues, and the reports will be truly queued in the background.

> A popular choice is to use a [Redis](https://laravel.com/docs/redis) store and [queue driver](https://laravel.com/docs/queues#driver-prerequisites), along with [Laravel Horizon](https://laravel.com/docs/horizon) for managing your Redis queues.

### Widget

You may add a reports widget to your dashboard to get a quick insight into your site's SEO status. Add the following to your `widgets` array within `config/statamic/cp.php` to show the latest report's score:

```php
'widgets' => [
    [
        'type' => 'seo_pro',
        'width' => 50,
    ]
],
```

## Advanced Configuration

### Publishing Config

You can publish SEO Pro's config for modification by running the following:

```
php artisan vendor:publish --tag="seo-pro-config"
```

### Publishing Views

You can publish SEO Pro's `sitemap.xml` and `humans.txt` views for modification by running the following:

```
php artisan vendor:publish --tag="seo-pro-views"
```

These views will be published into your `resources/views/vendor/seo-pro` directory for modification.

You may also override the default `meta.antlers.html` view, though it is not published by default. _**Important Note:** Overriding this view will require you to be mindful of updates as it will not be automatically maintained for you._

### Sitemap.xml

A `sitemap.xml` route is automatically generated for you.

If you disable SEO on the section or item level, the relevant section/item will automatically be discluded from the sitemap.

If you wish to completely disable the sitemap, change it's URL, or customize it's cache expiry, you can [publish the SEO Pro config](#advanced-configuration) and modify these settings within `config/statamic/seo-pro.php`.

If you wish to customize the contents of the `sitemap.xml` view, you may also [publish the SEO Pro views](#publishing-views) and modify the provided antlers templates within your `resources/views/vendor/seo-pro` folder.

You can also [extend the Sitemap class](https://github.com/statamic/seo-pro/pull/361) if you need more advanced control over query logic, etc.

### Humans.txt

A `humans.txt` route is automatically generated for you.

If you wish to completely disable humans.txt or change it's URL, you can [publish the SEO Pro config](#advanced-configuration) and modify these settings within `config/statamic/seo-pro.php`.

If you wish to customize the contents of the `humans.txt` view, you may also [publish the SEO Pro views](#publishing-views) and modify the provided antlers templates within your `resources/views/vendor/seo-pro` folder.

### Pagination Meta

By default, `canonical` URL meta will show pagination on `?page=2` and higher, with `rel="prev"` / `rel="next"` links when appropriate.

If you wish to customize or disable pagination, you can [publish the SEO Pro config](#advanced-configuration) and modify these settings within `config/statamic/seo-pro.php`.

### Twitter Card Meta

By default, `twitter:card` meta will be rendered using `summary_large_image`.

If you wish to change this to `summary`, you can [publish the SEO Pro config](#advanced-configuration) and modify your twitter card within `config/statamic/seo-pro.php`.

### Trailing Slashes in URLs

If you're using [statamic/ssg](https://github.com/statamic/ssg) and require trailing slashes on your URLs (ie. for Netlify's SEO tooling), you should enable trailing slashes in your SSG config.

Otherwise, you can manually enforce trailing slashes across your app by adding `\Statamic\Facades\URL::enforceTrailingSlashes()` to the boot method of your AppServiceProvider.

Both of these methods should enforce trailing slashes on most URLs across your app, including in your SEO meta and your sitemap.xml.

## GraphQL

If you're accessing content through Statamic's [GraphQL API](https://statamic.dev/graphql), you can render SEO meta on your entries and terms this way as well. For example, in an [entries query](https://statamic.dev/graphql#entries-query) you can access prerendered SEO meta `html` under `seo`:

```graphql
{
    entries {
        data {
            seo {
                html
            }
        }
    }
}
```

Or if you prefer to render your own SEO meta HTML by hand, you can access the SEO Cascade directly (which will respect your [Site Defaults](#site-defaults) and [Section Defaults](#section-defaults)):

```graphql
{
    entries {
        data {
            seo {
                site_name
                site_name_position
                site_name_separator
                title
                compiled_title
                description
                priority
                change_frequency
                og_title
                canonical_url
                alternate_locales {
                    url
                    site {
                        handle
                        locale
                    }
                }
                prev_url
                next_url
                home_url
                humans_txt
                twitter_card
                twitter_handle
                image {
                    url
                    permalink
                }
                last_modified(format: "Y-m-d")
            }
        }
    }
}
```

**Tip:** Feel free to browse the schema and test output through the GraphiQL explorer in your CP at `/cp/graphiql`.


## Uninstalling

To uninstall, run:

```
composer remove statamic/seo-pro
```

If you've saved any blueprints while SEO Pro was installed, an `seo` field will have been added to them. You will need to manually remove the `seo` field from the corresponding blueprints.
