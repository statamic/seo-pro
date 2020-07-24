# SEO Pro

![Statamic 3.0+](https://img.shields.io/badge/Statamic-3.0+-FF269E?style=for-the-badge&link=https://statamic.com)

📈 SEO Pro is an all-in-one site reporting, metadata wrangling, Open Graph managing, Twitter card making, sitemap generating, turnkey addon.

## SEO Pro is a Commercial Addon.

You can use it for free while in development, but requires a license to use on a live site. Learn more or buy a license on [The Statamic Marketplace](https://statamic.com/addons/statamic/seo-pro)!

## Installation

1. Install SEO Pro from the `Tools > Addons` section of your control panel, or via composer:

    ```
    composer require statamic/seo-pro
    ```

2. Add `{{ seo_pro:meta }}` somewhere between your `<head>` tags.

## Usage

SEO settings will cascade down from the global defaults, to the collection/taxonomy level, and finally to the entry/term level.

Empty meta tags will not be rendered, which allows you to optionally set your own tags with other means if you so choose.

### Defaults

Head to `Tools > SEO Pro > Site Defaults` and configure your default settings. The defaults will be used if you haven't set anything more specific at the collection or entry level.

> Values configured here will be saved into `content/seo.yaml`.

You may choose to pull data from other fields, enter hardcoded strings, or use Antlers templating. See [File Usage](#file-usage) for more details.

### Sections (Collections and Taxonomies)

Each section may be configured independently. You may opt to inherit values from the defaults and tweak as necessary.

> Values configured here will be saved into the `seo` array (within `inject`) in the respective section's yaml config.

You may disable a section by toggling the "Enabled" field when editing a section, or set `seo: false` within the `inject` array in that section's yaml config. Disabling a section will prevent it's items from being included in reports, the sitemap, and prevent the template tag from rendering anything.

### Entries and Terms

It's better to configure your collections and taxonomies to dynamically pull from fields. However, an SEO tab will be added to each item's publish page and you are free to override any values there.

> Values configured here will be saved into the `seo` array in the item's front-matter.

## File Usage

For advanced devs, you may bypass the CP and configure your SEO settings through files. There are 3 sorts of values you may save.

**Custom / hardcoded strings:**

```yaml
title: "A hardcoded string"
```

**Field references**

Prefix a field name with `@seo:` to have that field's value referenced automatically.

A field in a specific fieldset may be specified (this is how the CP will save them). The fieldset is completely optional and currently provides no additional benefit.

```yaml
title: "@seo:title"
title: "@seo:post/title"  # with optional fieldset
```

**Antlers Templating**

You may use Statamic Antlers templating in your strings. When doing this, the addon will not apply any automatic parsing rules (limiting the length of the description, for example).

```yaml
description: "{{ content | strip_tags | truncate:250:... }}"
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
