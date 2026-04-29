# Release Notes

## 7.7.0 (2026-04-29)

### What's new
- Redirects & Error Tracking [#545](https://github.com/statamic/seo-pro/issues/545) by @duncanmcclean

### What's fixed
- Update Laravel Pint [#546](https://github.com/statamic/seo-pro/issues/546) by @duncanmcclean
- Widget header tweaks [#547](https://github.com/statamic/seo-pro/issues/547) by @duncanmcclean



## 7.6.2 (2026-04-23)

### What's fixed
- Fix Antlers parsing for JSON-LD (site defaults) [#544](https://github.com/statamic/seo-pro/issues/544) by @joshuablum



## 7.6.1 (2026-04-21)

### What's fixed
- Fix serializable class errors [#542](https://github.com/statamic/seo-pro/issues/542) by @duncanmcclean



## 7.6.0 (2026-04-21)

### What's new
- Support custom JSON-LD schema in site defaults [#540](https://github.com/statamic/seo-pro/issues/540) by @duncanmcclean

### What's fixed
- Use `adaptiveWidth` prop in site selector [#539](https://github.com/statamic/seo-pro/issues/539) by @duncanmcclean



## 7.5.1 (2026-04-17)

### What's fixed
- Stop polling report info when navigating away [#531](https://github.com/statamic/seo-pro/issues/531) by @duncanmcclean
- Fix site defaults disappearing when using Antlers values [#534](https://github.com/statamic/seo-pro/issues/534) by @joshuablum
- Stack title and description on small widths in report summary [#537](https://github.com/statamic/seo-pro/issues/537) by @duncanmcclean
- Fix robots meta ignoring new fields when legacy array exists [#538](https://github.com/statamic/seo-pro/issues/538) by @duncanmcclean



## 7.5.0 (2026-03-31)

### What's new
- Allow disabling Glide for JSON-LD organization logo [#510](https://github.com/statamic/seo-pro/issues/510) by @samalisam-novu

### What's fixed
- Fix dark mode hover state on index page [#529](https://github.com/statamic/seo-pro/issues/529) by @duncanmcclean



## 7.4.0 (2026-03-30)

### What's new
- Add missing default values to site defaults [#520](https://github.com/statamic/seo-pro/issues/520) by @duncanmcclean

### What's fixed
- Refactor SEO field placeholders [#523](https://github.com/statamic/seo-pro/issues/523) by @duncanmcclean
- Fix squished Google preview image [#524](https://github.com/statamic/seo-pro/issues/524) by @duncanmcclean
- Reload preview images when asset is updated [#525](https://github.com/statamic/seo-pro/issues/525) by @duncanmcclean
- Previews field shouldn't be localizable [#526](https://github.com/statamic/seo-pro/issues/526) by @duncanmcclean
- Fix "Array to string conversion" error when resolving placeholders [#527](https://github.com/statamic/seo-pro/issues/527) by @duncanmcclean



## 7.3.0 (2026-03-13)

### What's new
- Transform preview images using Glide [#516](https://github.com/statamic/seo-pro/issues/516) by @duncanmcclean

### What's fixed
- Remember tab in site defaults publish form [#517](https://github.com/statamic/seo-pro/issues/517) by @duncanmcclean
- Fix border of twitter preview in dark mode [#518](https://github.com/statamic/seo-pro/issues/518) by @duncanmcclean



## 7.2.0 (2026-03-09)

### What's new
- Supports Laravel 13 [#502](https://github.com/statamic/seo-pro/issues/502) by @duncanmcclean

### What's fixed
- Fix error when generating JSON-LD breadcrumbs [#513](https://github.com/statamic/seo-pro/issues/513) by @duncanmcclean
- Fix sitemap cache invalidation [#514](https://github.com/statamic/seo-pro/issues/514) by @duncanmcclean



## 7.1.1 (2026-02-27)

### What's fixed
- Antlers parsing and cascade hydration #507 by @jasonvarga
- Default site name should be APP_NAME #505 by @edalzell



## 7.1.0 (2026-02-23)

### What's improved
- Add spreadsheet to sitemaps #504 by @duncanmcclean
- Use `RunsUpdateScripts` trait in test #500 by @duncanmcclean

### What's fixed
- Add missing GraphQL fields #503 by @samalisam-novu



## 7.0.3 (2026-02-18)

### What's fixed
- Fixed JSON-lD entity type comparison #499 by @samalisam-novu



## 7.0.2 (2026-02-06)

### What's fixed
- Fix disabled SEO fields (again!) #496 by @duncanmcclean



## 7.0.1 (2026-01-30)

### What's fixed
- Avoid "From Field" dropdown in site defaults #494 by @duncanmcclean
- Fix disabled SEO fields #493 by @duncanmcclean
- Display custom collection icon in section default listing #492 by @duncanmcclean
- Fix field conditions in JSON-LD tab #491 by @duncanmcclean



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
  - If this doesn't happen, you should run the update script manually via `php please updates:run 7.0.0-beta.1 --package=statamic/seo-pro`.
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
