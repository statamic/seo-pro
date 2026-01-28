# Release Notes

## 7.0.0 (2026-01-28)

### What's new
- Updated for Statamic 6
- Localizable Site Defaults #436 by @duncanmcclean
- Structured Data (JSON-LD) support #440 by @duncanmcclean
- Search Engine & Social Card Previews #442 by @duncanmcclean
- Added report rules for ideal title & description lengths #443 by @jackmcdade
- Allow enforcement of trailing slashes on urls and links #393 by @jesseleite

### What's improved
- Robots have been split off into their own section by @jackmcdade
- "Enabled" toggle in SEO Settings now controls the visibility of SEO fields
- Site Defaults are now stored using Statamic's Addon Settings feature #432 by @duncanmcclean

### Breaking changes
- Dropped support for PHP 8.2 and Laravel 11
- Site Defaults are now stored using Statamic's [Addon Settings](https://statamic.dev/addons/building-an-addon#settings) feature.
  - SEO Pro will attempt to move the `content/seo.yaml`  file to `resources/addons/seo-pro.yaml` during the upgrade process. It will also update the structure of the YAML file.
- Site Defaults can now be localized.
  - When multi-site is enabled, SEO Pro will have configured origins for your non-default sites pointing at the default site to best mirror the previous behavior where all sites used the same defaults.
  - You can update these origins via the "Site Defaults" page in the Control Panel.
- The `SeoProSiteDefaultsSaved` event has been renamed to `SiteDefaultsSaved`.
  - The `$defaults` property is now a `LocalizedSiteDefaults` object.



## 7.0.0-beta.7 (2026-01-15)

### What's improved

- Requires Alpha 18 by @duncanmcclean
- Added translation for "Visit URL" by @duncanmcclean
- `useArchitecturalBackground` is now imported from Statamic by @duncanmcclean



## 7.0.0-beta.6 (2025-12-11)

### What's fixed
- Fixed dark mode issues in Page Details modal #477 by @duncanmcclean
- Added min-width on dropdowns #476 by @helloDanuk



## 7.0.0-beta.5 (2025-12-08)

### What's fixed
- German translations #474 by @helloDanuk
- Column labels on reports index page are now properly translated by @duncanmcclean
- Fixed `HumansTest` by @duncanmcclean
- Dropped `axios` dependency #472 by @duncanmcclean
- Avoid constructing SEO Preview image URL when inherited value is empty by @duncanmcclean
- Fixed fieldtype icon by @duncanmcclean



## 7.0.0-beta.4 (2025-11-24)

### What's fixed
- Tweak site default translations
- Added `text-pretty` to rows in report summary table
- Don't rely on pages being in the cache
- Fixed an error when deleting last report
- Fixed "Delete null" in report deletion modal
- Clicking the site score should take you to the report
- Fixed alignment of site score
- Avoid pushing query params when visiting the reports show page
- Fixed some more translation strings #458 by @helloDanuk

### Breaking changes
- Dropped support for Laravel 11 & PHP 8.2 #468 by @duncanmcclean



## 7.0.0-beta.3 (2025-11-17)

### What's fixed
- Truncated options in source fieldtype #455 by @duncanmcclean
- Fixed translation strings #454 by @duncanmcclean



## 7.0.0-beta.2 (2025-11-12)

### What's fixed
- Fixed an issue where the addon settings update script wouldn't run when updating by @duncanmcclean



## 7.0.0-beta.1 (2025-11-12)

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
- Updated German translations #444 by @helloDanuk

### Breaking changes
- Dropped support for PHP 8.1 and Laravel 10
- Site Defaults are now stored using Statamic's [Addon Settings](https://statamic.dev/addons/building-an-addon#settings) feature.
  - SEO Pro will attempt to move the `content/seo.yaml`  file to `resources/addons/seo-pro.yaml` during the upgrade process. It will also update the structure of the YAML file.
- Site Defaults can now be localized.
  - When multi-site is enabled, SEO Pro will have configured origins for your non-default sites pointing at the default site to best mirror the previous behavior where all sites used the same defaults. 
  - You can update these origins via the "Site Defaults" page in the Control Panel.
- The `SeoProSiteDefaultsSaved` event has been renamed to `SiteDefaultsSaved`. 
  - The `$defaults` property is now a `LocalizedSiteDefaults` object.
