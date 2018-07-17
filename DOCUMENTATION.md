## Installation

Unzip and place the `SeoPro` directory in your `site/addons` directory.

Add `{{ seo_pro:meta }}` somewhere between your `<head>` tags.

## Usage

SEO settings will cascade down from the global defaults, to the collection level, and finally to the entry level.

### Defaults

Head over to `Tools > SEO Pro > Defaults` and configure your default settings. The defaults will be used if you haven't set anything more specific at the collection or entry level.

> Values configured here will be saved into the `defaults` array in `site/settings/seo_pro.yaml`.

You may choose to pull data from other fields, enter hardcoded strings, or use Antlers templating. See [File Usage](#file-usage) for more details.

### Sections (Collections and Taxonomies)

Each section may be configured independently. You may opt to inherit values from the defaults and tweak as necessary.

> Values configured here will be saved into the `seo` array in the respective section's `folder.yaml`.

You may disable a section by toggling the "Enabled" field when editing a section, or set `seo: false` in their `folder.yaml`. Disabling a section will prevent it's items from being included in reports, and prevent the template tag from rendering anything.

### Entries

It's better to configure your collection to dynamically pull from fields. However, an SEO tab will be added to the entry's publish page and you are free to override any values there.

> Values configured here will be saved into the `seo` array in the entry's front-matter.


## File Usage

For advanced devs, you may bypass the CP and configure your SEO settings through files. There are 3 sorts of values you may save.

**Custom / hardcoded strings:**

``` yaml
title: "A hardcoded string"
```

**Field references**

Prefix a field name with `@seo:` to have that field's value referenced automatically.

A field in a specific fieldset may be specified (this is how the CP will save them). The fieldset is completely optional and currently provides no additional benefit.

``` yaml
title: "@seo:title"
title: "@seo:post/title"  # with optional fieldset
```

**Antlers Templating**

You may use Statamic Antlers templating in your strings. When doing this, the addon will not apply any automatic parsing rules (limiting the length of the description, for example).

``` yaml
description: "{{ content | striptags | truncate:250:... }}"
```

## Reports

You may generate an SEO report that checks all the pages of your site against a number of tests. The tests include mandatory items like title tag uniqueness, or suggested items like URLs being no more than 3 segments. Failing a mandatory item will result in a fail where failing a suggested item will result in a warning.

Reports will stick around until deleted, so you are free to compare reports to see how you are progressing.

You may generate a report through the Control Panel, or by running `php please seo:report:generate`.

### Queuing Reports

Depending on the size of your site, generating a report may take a while.

We generate them in the "background" using a middleware. It's possible that this could interfere with other middleware. To prevent this, you can enable queues, and the reports will be truly queued in the background.

The simplest way to do this locally is with Redis. Add `QUEUE_DRIVER=redis` to your `.env` file, then run `php please queue:listen`.

### Widget

You may add a reports widget to your dashboard to get a quick insight into your site's SEO status. Add the following to `site/settings/cp.yaml` to show the latest report's score:

``` yaml
widgets:
  -
    type: seo_pro.reports
```
