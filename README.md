# SEO Pro ![Statamic](https://img.shields.io/badge/statamic-2.9.9-blue.svg?style=flat-square)
> An SEO addon for Statamic professionals.


## Installation

Unzip and place the `SeoPro` directory in your `site/addons` directory.

Add `{{ seo_pro:meta }}` somewhere between your `<head>` tags.

## Usage

SEO settings will cascade down from the global defaults, to the collection level, and finally to the entry level.

### Defaults

Head over to `Tools > SEO Pro > Defaults` and configure your default settings. The defaults will be used if you haven't set anything more specific at the collection or entry level.

> Values configured here will be saved into the `defaults` array in `site/settings/seo_pro.yaml`.

You may choose to pull data from other fields, enter hardcoded strings, or use Antlers templating. See [File Usage](#file-usage) for more details.

### Collections

Each collection may be configured independently. You may opt to inherit values from the defaults and tweak as necessary.

> Values configured here will be saved into the `seo` array in the respective collection's respective `folder.yaml`.

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
