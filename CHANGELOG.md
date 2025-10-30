# Release Notes

## 7.0.0 (2025-xx-xx)

### What's new
- Updated the Control Panel for Statamic 6
- Localizable Site Defaults #436 by @duncanmcclean
- Structured Data (JSON-LD) support #440 by @duncanmcclean
- Search Engine & Social Card Previews #442 by @duncanmcclean
- Added report rules for ideal title & description lengths #443 by @jackmcdade
- Allow enforcement of trailing slashes on urls and links #393 by @jesseleite

### What's improved
- Site Defaults are now stored using Statamic's Addon Settings feature #432 by @duncanmcclean
- Robots have been split off into their own section by @jackmcdade
- "Enabled" toggle in SEO Settings now controls the visibility of SEO fields

### Breaking changes
- Dropped support for PHP 8.1 and Laravel 10
- Site Defaults are now stored using Statamic's [Addon Settings](https://statamic.dev/addons/building-an-addon#settings) feature.
  - SEO Pro will attempt to move the `content/seo.yaml`  file to `resources/addons/seo-pro.yaml` during the upgrade process. It will also update the structure of the YAML file.
- Site Defaults can now be localized.
  - When multi-site is enabled, SEO Pro will have configured origins for your non-default sites pointing at the default site to best mirror the previous behavior where all sites used the same defaults. 
  - You can update these origins via the "Site Defaults" page in the Control Panel.
- The `SeoProSiteDefaultsSaved` event has been renamed to `SiteDefaultsSaved`. 
  - The `$defaults` property is now a `LocalizedSiteDefaults` object.