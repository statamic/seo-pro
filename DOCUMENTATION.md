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

You may choose to pull data from other fields, enter hardcoded strings, or use Antlers templating. See [File Usage](#file-usage) for more details.

If you're using Statamic's [Multi-Site](https://statamic.dev/multi-site) feature, site defaults are localizable, meaning defaults can be set per site. You may configure an "origin" for each localization, determining which site to inherit values from if none are set for the current site.

#### Storage

Site Defaults are stored in `resources/addons/seo-pro.yaml` by default, but can be moved to the database via the `php please install:eloquent-driver` command.

#### Static Caching

If you're using [Static Caching](https://statamic.dev/static-caching), you may configure invalidation rules to be triggered when SEO Pro's site defaults are updated:

```php
// config/statamic/static_caching.php

'invalidation' => [
    'rules' => [
        'seo_pro_site_defaults' => [
            'urls' => [
                '/*',
            ],
        ],
    ],
],
```

### Section Defaults

Each section may be configured independently at the Collection / Taxonomy level. Head to `Tools > SEO Pro > Section Defaults` to configure default settings at this level. You may opt to inherit values from the defaults and tweak as necessary.

> Values configured here will be saved into the `seo` array (within `inject`) in the respective section's yaml config.

You may disable a section by toggling the "Enabled" field when editing a section, or set `seo: false` within the `inject` array in that section's yaml config. Disabling a section will prevent it's items from being included in reports, the sitemap, and prevent the template tag from rendering anything.

### Entries and Terms

It's better to configure your collections and taxonomies to dynamically pull from fields. However, an SEO tab will be added to each item's publish page and you are free to override any values there.

> Values configured here will be saved into the `seo` array in the item's front-matter.

### Assets

If you wish to use assets in your meta, you can [publish the SEO Pro config](#advanced-configuration) and specify an asset container, as well as a folder and glide preset to be used.

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

## JSON-LD

You may configure data for the Organization/Person objects, as well as enable breadcrumb data from your Site Defaults:

![JSON-LD Tab on Site Defaults page](https://raw.githubusercontent.com/statamic/seo-pro/refs/heads/7.x/docs-site-defaults-json-ld.png)

You can then configure JSON-LD objects for your content via section defaults, which can be overridden on a per-entry/term basis.

You can even use Antlers to pull data from fields as necessary:

![JSON-LD Schema field on Section Defaults page](https://raw.githubusercontent.com/statamic/seo-pro/refs/heads/7.x/docs-json-ld-schema.png)

If you want to use any tags or modifiers in your schema, you may need to [add them to an allowlist](https://statamic.dev/frontend/antlers#opting-into-tags-and-modifiers) in Statamic's `antlers.php` config.

The "Organization Logo" will be dynamically resized using Glide to comply with the [JSON-LD schema](https://developers.google.com/search/docs/appearance/structured-data/organization). If you'd prefer to disable this behaviour, you may disable the `json_ld.use_glide_for_logo` option in your config.

```php
// config/statamic/seo-pro.php

'json_ld' => [
    'use_glide_for_logo' => false,
],
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

## Robots.txt and AI Crawlers

SEO Pro can manage a physical `robots.txt` file at `public_path('robots.txt')`, which is typically `public/robots.txt`. The file is served directly by the web server rather than through a dynamic route, making it compatible with web server rules and Statamic's Full Measure static caching.

This is separate from the Robots controls in Site Defaults, Section Defaults, entries, and terms. Those controls generate page-level `<meta name="robots">` directives for indexing, following links, archiving, snippets, and image indexing; they do not change the physical `robots.txt` file.

Users need the **Edit robots.txt settings** permission to access this feature. Head to `Tools > SEO Pro > Crawling & AI`, then select the **robots.txt** tab to configure the file.

### Saving and Generating

The Control Panel provides two separate actions:

- **Save** persists the policy without changing the physical file. If a managed file no longer matches the saved policy, SEO Pro indicates that it needs to be generated again.
- **Generate** asks for confirmation, writes or overwrites the physical file, and then persists the policy and generation metadata. The public directory and addon settings storage must both be writable.

Managed mode includes a live preview that updates as you edit the policy. Preview requests do not write the file or persist settings. Custom source mode does not display a separate preview because the entered source is the file content. SEO Pro normalizes line endings and ensures a final newline but does not add any generated directives in custom mode.

If no physical file exists, the Control Panel prompts you to create one. If an existing file is not currently managed by SEO Pro, it displays a warning and offers to import the contents into custom mode. Import is available only for readable UTF-8 files no larger than 500 KiB; an unsupported file can still be replaced by selecting **Generate**.

SEO Pro identifies a managed file using the checksum stored with its generation metadata. Manually changing the physical file causes it to be treated as unmanaged. The `# Generated by Statamic SEO Pro` comment in managed output is informational and is a valid `robots.txt` comment.

Generation is idempotent. When the rendered file and saved policy are already current, SEO Pro does not rewrite the file, update its generation timestamp, save the settings again, or dispatch another generation event. Concurrent generation attempts are rejected.

SEO Pro preserves both the previous physical file and the complete addon settings while generating. If writing or saving fails, it restores both resources before reporting the error. A successful generation dispatches `Statamic\SeoPro\Events\RobotsTxtGenerated` when the physical file changes.

### Managed Policies

Managed mode creates a general `User-agent: *` group from the allowed and disallowed paths. It also provides presets and individual access controls for three categories of known AI crawlers:

| Category | Crawlers |
| --- | --- |
| AI search | `OAI-SearchBot`, `Claude-SearchBot`, `PerplexityBot` |
| User-directed agents | `ChatGPT-User`, `Claude-User`, `Perplexity-User` |
| Model training | `GPTBot`, `ClaudeBot`, `Google-Extended`, `Applebot-Extended`, `CCBot`, `Meta-ExternalAgent`, `Bytespider` |

The included presets configure those categories as follows:

| Preset | AI search | User-directed agents | Model training |
| --- | --- | --- | --- |
| Neutral | No explicit rule | No explicit rule | No explicit rule |
| Discoverable, not trainable | Allow | Allow | Block |
| Full AI access | Allow | Allow | Allow |
| AI search only | Allow | Block | Block |
| Block AI access | Block | Block | Block |

You can fine-tune the individual controls after selecting a preset. Explicitly allowed crawler groups receive the configured paths and Content Signals. Blocked crawler groups receive `Disallow: /`. Crawler directives are voluntary requests and are not security controls; protect private content with authentication or other server-side authorization.

### Content Signals

SEO Pro can emit a `Content-Signal` directive expressing preferences for search indexing, AI input or grounding, model training, and content use. Content-use choices include immediate use, reference with a link back, and full use. These signals are experimental preferences and do not technically prevent fetching, scraping, or reuse.

### Sitemap Discovery and Multi-Site

When the SEO Pro sitemap is enabled, managed mode can add its absolute URL to `robots.txt`. Sitemap URLs may be resolved automatically from Statamic's sites or entered as custom canonical URLs.

The robots policy is shared by every Statamic site using the same public directory. In a multi-site installation, automatic discovery adds one deduplicated `Sitemap:` line for each site authority. Sites on different root domains get separate sitemap lines, while directory-based sites on the same authority share one.

When a Statamic site uses a relative URL, automatic discovery uses the current environment's hostname. Choose **Custom canonical URLs** and enter the production sitemap URL for each root domain when generating a file that will be committed and deployed to another environment.

### Version Control and Deployments

The generated file is intended to be committed with the Statamic project. If Statamic's Git integration is enabled, SEO Pro adds the physical `robots.txt` path to Git's tracked paths so it can be committed with the addon settings. The `RobotsTxtGenerated` event is also available for custom integrations.

There are two supported deployment workflows:

1. Generate and commit the file. Use custom canonical sitemap URLs when local and production hostnames differ.
2. Generate the file during deployment. Automatic sitemap discovery then uses that environment's configuration and hostname.

You can generate the file from the command line during either workflow:

```shell
php please seo-pro:generate:robots-txt
```

Robots settings and generation metadata use Statamic's addon settings repository. By default, they are stored with the other SEO Pro settings in `resources/addons/seo-pro.yaml`.

## Redirects

SEO Pro includes a redirect manager that lets you define URL redirects, automatically create redirects when slugs change, and track 404 errors.

### Managing Redirects

Head to `Tools > SEO Pro > Redirects` to create and manage redirects. Each redirect has a source URL, a destination URL, and a response code (301 or 302). Redirects can also be enabled or disabled.

![Redirects listing](https://raw.githubusercontent.com/statamic/seo-pro/refs/heads/7.x/docs-redirects.png)

#### Importing & Exporting

You can import and export redirects as CSV files. Click the "Import/Export" dropdown on the Redirects listing page to access these options.

**Exporting** will download a CSV file containing all redirects for your authorized sites, with the following columns: `source`, `destination`, `response_code`, `enabled`, and `description`.

**Importing** accepts a CSV file with a header row. The `source` and `destination` columns are required. The `response_code`, `enabled`, and `description` columns are optional — if omitted, new redirects will use the default response code and be enabled by default.

If a redirect already exists with the same source URL (on the selected site), it will be updated rather than duplicated. When updating, only the columns present in the CSV will be changed — omitted columns will retain their existing values.

#### Wildcards

You can use wildcards in your source URLs. Each `*` captures a segment, and you can reference them in the destination with `$1`, `$2`, etc:

- Source: `/blog/*` → Destination: `/articles/$1`
- Source: `/blog/*/posts/*` → Destination: `/articles/$1/entries/$2`

Exact matches always take priority over wildcard matches.

#### Query Strings

By default, query strings from the original URL are retained when redirecting. You may disable this behaviour in your config:

```php
// config/statamic/seo-pro.php

'redirects' => [
    'preserve_query_string' => false,
],
```

#### Storage

By default, redirects are stored as YAML files in the `content/seo-pro/redirects` directory. You can change this in the config:

```php
// config/statamic/seo-pro.php

'redirects' => [
    'driver' => 'file',
    'directory' => base_path('content/seo-pro/redirects'),
],
```

Alternatively, you may store redirects in the database by changing the driver:

```php
// config/statamic/seo-pro.php

'redirects' => [
    'driver' => 'database',
],
```

Then run `php please seo-pro:database-redirects` to publish the migration and import existing redirects.

#### Hit Tracking

Out of the box, SEO Pro tracks the number of hits and the timestamp of the last hit for each redirect. If you store redirects in flat files, this can lead to frequent file changes creating noise in version control.

You may disable hit tracking in the config:

```php
// config/statamic/seo-pro.php

'redirects' => [
    'track_hits' => false,
],
```

### Multi-Site

Redirects and errors are scoped to individual sites. Each redirect belongs to a single site, and the source URL is stored relative to the site root. For example, a redirect with the source `/about` on the French site will only match requests to `example.com/fr/about` (or `example.fr/about`, depending on your site configuration).

When using the Control Panel, redirects and errors are filtered to the currently selected site. The site can be changed using the site filter in the listing.

When you enable multi-site on an existing install via `php please multisite`, SEO Pro will automatically move your existing redirect and error files into subdirectories for the default site.

### Automatic Redirects

SEO Pro can automatically create redirects when an entry or term's slug changes. This prevents broken links when content is reorganized.

To enable automatic redirects, set the `SEO_PRO_AUTOMATIC_REDIRECTS` environment variable to `true`:

```env
SEO_PRO_AUTOMATIC_REDIRECTS=true
```

By default, automatic redirects apply to all collections and taxonomies. You may limit this to specific collections or taxonomies in the config:

```php
// config/statamic/seo-pro.php

'redirects' => [
    'automatic_redirects' => [
        'enabled' => env('SEO_PRO_AUTOMATIC_REDIRECTS', false),
        'collections' => ['pages', 'posts'],
        'taxonomies' => ['tags'],
    ],
],
```

When an entry's slug changes, a redirect is created from the old URL to the new one, using the default response code (301 by default). If a redirect already exists for the old URL, its destination is updated rather than creating a duplicate.

You may change the default response code in the config:

```php
// config/statamic/seo-pro.php

'redirects' => [
    'default_response_code' => 301,
],
```

In a multi-site setup, when an entry's slug changes, redirects are also created for any localizations that share the same slug change, each pointing to the localized version of the content.

Self-referencing redirects (where the source and destination are the same URL) are automatically cleaned up.

### Error Tracking

SEO Pro can track 404 errors, giving you visibility into broken links on your site. When a request doesn't match a redirect, the URL is recorded as an error.

To enable error tracking, set the `SEO_PRO_TRACK_ERRORS` environment variable:

```env
SEO_PRO_TRACK_ERRORS=true
```

Errors can be viewed at `Tools > SEO Pro > Errors`. From there, you can see each error's URL, hit count, and last hit time. Each error also has a link to quickly create a redirect for that URL.

When a redirect is created, any errors matching the redirect's source URL are automatically deleted.

#### Purging Old Errors

It's easy to accumulate lots of errors over time. To keep things tidy, SEO Pro will automatically purge errors older than 30 days.

You may customize the purge threshold in the config:

```php
// config/statamic/seo-pro.php

'redirects' => [
    'errors' => [
        'purge_after_days' => 30,
    ],
],
```

On a public site, bots probing for unique URLs can create an unbounded number of errors between purges. To bound the total, set `max_errors` to a cap. When the purge runs and the number of errors exceeds the cap, the excess is purged: errors that have never been hit go first, then those with the fewest hits, then those hit least recently. Frequently-hit 404s, the best redirect candidates, survive longest. The default of `0` disables the cap.

```php
// config/statamic/seo-pro.php

'redirects' => [
    'errors' => [
        'max_errors' => 1000,
    ],
],
```

You may also run the command manually:

```
php please seo-pro:purge-errors
```

#### Storage

By default, errors are stored in the `storage/statamic/seopro/errors` directory. You can change this in the config:

```php
// config/statamic/seo-pro.php

'redirects' => [
   'errors' => [
      'driver' => 'file',
      'directory' => storage_path('statamic/seopro/errors'),
   ],
],
```

Errors can be stored in the database independently of redirects:

```php
// config/statamic/seo-pro.php

'redirects' => [
    'errors' => [
        'driver' => 'database',
    ],
],
```

Then run `php please seo-pro:database-errors` to publish the migration and import existing errors.

#### Widget

You can add a recent errors widget to your dashboard to see the latest 404s at a glance:

```php
// config/statamic/cp.php

'widgets' => [
    [
        'type' => 'recent_errors',
        'width' => 50,
        'limit' => 5,
    ],
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

When using multi-site, SEO Pro creates unique sitemaps for each _domain_. If you use multi-site with one domain, a _single_ sitemap gets created and the [`hreflang` attribute](https://developers.google.com/search/docs/specialty/international/localized-versions#sitemap) is getting added to the XML making it easier for search engines to discover localizations of content across your sites. 

Probably easier to understand with an example:

- Site A: `https://example.com`
- Site B: `https://example.com/de-de`
- Site C: `https://example.fr`

This structure will result in the following sitemaps being created:

- `https://example.com/sitemap.xml` includes pages from Site A & Site B with the `hreflang` attribute added to them
- `https://example.fr/sitemap.xml` includes pages from Site C

This follows recommendations and best practices provided by Google and other search engines. It prevents sitemaps linking to pages from other domains which is considered a "no no".

If you disable SEO on the section or item level, the relevant section/item will automatically be discluded from the sitemap.

If you wish to completely disable the sitemap, change it's URL, or customize it's cache expiry, you can [publish the SEO Pro config](#advanced-configuration) and modify these settings within `config/statamic/seo-pro.php`.

Custom URLs may be added to the sitemap via the `additional` hook:

```php
// app/Providers/AppServiceProvider.php

use Statamic\SeoPro\Sitemap\Page;
use Statamic\SeoPro\Sitemap\Sitemap;

public function boot(): void
{
    // ...

    Sitemap::hook('additional', function ($payload, $next) {
        $payload->items->push((new Page)->with([
            'canonical_url' => url('additional-item'),
            'last_modified' => \Carbon\Carbon::parse('2025-01-01'),
            'change_frequency' => 'monthly',
            'priority' => 0.5,
        ]));

        return $next($payload);
    });
}
```

If you wish to customize the contents of the `sitemap.xml` view, you may also [publish the SEO Pro views](#publishing-views) and modify the provided antlers templates within your `resources/views/vendor/seo-pro` folder.

You can also [extend the Sitemap class](https://github.com/statamic/seo-pro/pull/361) if you need more advanced control over query logic, etc.

### Humans.txt

A `humans.txt` route is automatically generated for you.

If you wish to completely disable humans.txt or change it's URL, you can [publish the SEO Pro config](#advanced-configuration) and modify these settings within `config/statamic/seo-pro.php`.

If you wish to customize the contents of the `humans.txt` view, you may also [publish the SEO Pro views](#publishing-views) and modify the provided antlers templates within your `resources/views/vendor/seo-pro` folder.

### Pagination Meta

By default, `canonical` URL meta will show pagination on `?page=2` and higher, with `rel="prev"` / `rel="next"` links when appropriate.

This only works with pagination handled by Statamic itself, like the `paginate` parameter on the [collection](https://statamic.dev/tags/collection) and [taxonomy](https://statamic.dev/tags/taxonomy) tags. SEO Pro reads the current page and the previous/next URLs from the paginator those tags provide.

If you're querying entries manually or paginating other items, like an Eloquent model, SEO Pro won't know about it, so the canonical stays on the unpaginated base URL with no `rel="prev"` / `rel="next"` links. To fix this, store your paginator in [Blink](https://statamic.dev/backend-apis/blink-cache) under the `tag-paginator` key before the `{{ seo_pro:meta }}` tag renders. SEO Pro accepts any standard Laravel `LengthAwarePaginator`:

```php
use Statamic\Facades\Blink;
use Statamic\Facades\Entry;

$paginator = Entry::query()->where('collection', 'blog')->paginate(10);

Blink::put('tag-paginator', $paginator);
```

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
