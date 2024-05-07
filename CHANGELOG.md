# Changelog
All notable changes to **IP Locator** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **IP Locator** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.11.0] - 2024-05-07

### Changed
- The plugin now adapts its requirements to the PSR-3 loaded version.

## [3.10.3] - 2024-05-04

### Fixed
- PHP error when DecaLog is not installed.

## [3.10.2] - 2024-05-04

### Changed
- Updated DecaLog SDK from version 3.0.0 to version 4.1.0.
- Updated ActionScheduler from version 3.7.3 to version 3.7.4.
- Minimal required WordPress version is now 6.2.

## [3.10.1] - 2024-03-25

### Changed
- Updated Action Scheduler from 3.5.2 to 3.7.3.

### Fixed
- IP Locator may interfere with WooCommerce table migration (thanks to [Mil1](https://github.com/Mil1)).

## [3.10.0] - 2024-03-02

### Added
- Compatibility with WordPress 6.5.

### Changed
- Minimal required WordPress version is now 6.1.
- Minimal required PHP version is now 8.1.

## [3.9.0] - 2023-10-25

### Added
- Compatibility with WordPress 6.4.

### Fixed
- With PHP 8.2, in some edge cases, deprecation warnings may be triggered when viewing analytics.

## [3.8.0] - 2023-07-12

### Added
- Compatibility with WordPress 6.3.

### Changed
- The color for `shmop` test in Site Health is now gray to not worry to much about it (was previously orange).

## [3.7.1] - 2023-03-02

### Fixed
- [SEC004] CSRF vulnerability / [CVE-2023-27444](https://www.cve.org/CVERecord?id=CVE-2023-27444) (thanks to [Mika](https://patchstack.com/database/researcher/5ade6efe-f495-4836-906d-3de30c24edad) from [Patchstack](https://patchstack.com)).

## [3.7.0] - 2023-02-24

The developments of PerfOps One suite, of which this plugin is a part, is now sponsored by [Hosterra](https://hosterra.eu).

Hosterra is a web hosting company I founded in late 2022 whose purpose is to propose web services operating in a European data center that is water and energy efficient and ensures a first step towards GDPR compliance.

This sponsoring is a way to keep PerfOps One plugins suite free, open source and independent.

### Added
- Compatibility with WordPress 6.2.

### Changed
- Improved loading by removing unneeded jQuery references in public rendering (thanks to [Kishorchand](https://github.com/Kishorchandth)).
- The source of IP data is now hosted on Hosterra.

### Fixed
- In some edge-cases, detecting IP may produce PHP deprecation warnings (thanks to [YR Chen](https://github.com/stevapple)).

## [3.6.0] - 2022-10-06

### Added
- Compatibility with WordPress 6.1.
- Compatibility with PHP 8.2.
- There's a new warning in admin pages when PHP Intl extension is not installed.
- [WPCLI] The results of `wp location` commands are now logged in [DecaLog](https://wordpress.org/plugins/decalog/).

### Changed
- Improved primary language detection.
- Improved ephemeral cache in analytics.
- Updated Action Scheduler from 3.4.0 to 3.5.2.
- [WPCLI] The results of `wp location` commands are now prefixed by the product name.
- Improved resiliency to partially wrong or corrupted ICU data.

### Fixed
- [SEC003] Moment.js library updated to 2.29.4 / [Regular Expression Denial of Service (ReDoS)](https://github.com/moment/moment/issues/6012).
- With some versions of Intl PHP extension, language detection may be wrong.
- ICU data may be partially wrong.

## [3.5.0] - 2022-04-21

### Added
- Compatibility with WordPress 6.0.

### Changed
- Site Health page now presents a much more realistic test about object caching.
- Updated DecaLog SDK from version 2.0.2 to version 3.0.0.
- Updated Action Scheduler from 3.2.1 to 3.4.0.

### Fixed
- [SEC002] Moment.js library updated to 2.29.2 / [CVE-2022-24785](https://github.com/advisories/GHSA-8hfj-j24r-96c4).

## [3.4.1] - 2022-01-17

### Fixed
- The Site Health page may launch deprecated tests.

## [3.4.0] - 2022-01-17

### Added
- Compatibility with PHP 8.1.

### Changed
- Updated DecaLog SDK from version 2.0.0 to version 2.0.2.
- Updated PerfOps One library from 2.2.1 to 2.2.2.
- Refactored cache mechanisms to fully support Redis and Memcached.
- Improved bubbles display when width is less than 500px (thanks to [Pat Ol](https://profiles.wordpress.org/pasglop/)).
- The tables headers have now a better contrast (thanks to [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/)).

### Fixed
- Object caching method may be wrongly detected in Site Health status (thanks to [freshuk](https://profiles.wordpress.org/freshuk/)).
- The console menu may display an empty screen (thanks to [Renaud Pacouil](https://www.laboiteare.fr)).
- There may be name collisions with internal APCu cache.
- An innocuous Mysql error may be triggered at plugin activation.

## [3.3.0] - 2021-12-07

### Added
- Compatibility with WordPress 5.9.
- New button in settings to install recommended plugins.
- The available hooks (filters and actions) are now described in `HOOKS.md` file.

### Changed
- Improved update process on high-traffic sites to avoid concurrent resources accesses.
- Better publishing frequency for metrics.
- Updated labels and links in plugins page.
- X axis for graphs have been redesigned and are more accurate.
- Updated the `README.md` file.

### Fixed
- Country translation with i18n module may be wrong.

## [3.2.0] - 2021-09-07

### Added
- It's now possible to hide the main PerfOps One menu via the `poo_hide_main_menu` filter or each submenu via the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters (thanks to [Jan Thiel](https://github.com/JanThiel)).

### Changed
- Updated DecaLog SDK from 1.2.0 to 2.0.0.
- Updated Action Scheduler from 3.1.4 to 3.2.1.

### Fixed
- There may be name collisions for some functions if version of WordPress is lower than 5.6.
- The main PerfOps One menu is not hidden when it doesn't contain any items (thanks to [Jan Thiel](https://github.com/JanThiel)).
- In some very special conditions, the plugin may be in the default site language rather than the user's language.
- The PerfOps One menu builder is not compatible with Admin Menu Editor plugin (thanks to [dvokoun](https://wordpress.org/support/users/dvokoun/)).

## [3.1.2] - 2021-08-11

### Changed
- New redesigned UI for PerfOps One plugins management and menus (thanks to [Loïc Antignac](https://github.com/webaxones), [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/), [Axel Ducoron](https://github.com/aksld), [Laurent Millet](https://profiles.wordpress.org/wplmillet/), [Samy Rabih](https://github.com/samy) and [Raphaël Riehl](https://github.com/raphaelriehl) for their invaluable help).

### Fixed
- In some conditions, the plugin may be in the default site language rather than the user's language.
- Purging analytics history may produce a PHP notice.

## [3.1.1] - 2021-06-22

### Fixed
- wp.org distribute a Release Candidate, not the 3.1.0 version.

## [3.1.0] - 2021-06-22

### Added
- Traces now include metrics collation span.
- New option, available via settings page and wp-cli, to disable/enable metrics collation.

### Changed
- Updated DecaLog SDK.
- Updated developer's documentation.
- [WP-CLI] `location status` command now displays DecaLog SDK version too.

### Fixed
- Analytics and historical data options are not saved, nor applied.

## [3.0.0] - 2021-05-25

### Added
- Compatibility with WordPress 5.8.
- The settings screen now displays a warning if IP data auto-updates is not supported.
- IP locator now supports Google Cloud-LB country detection.
- Compatibility with future DecaLog SDK.
- [BC] The version of IP Locator API is now `v3`.

### Changed
- As the IP Database provider (WebNet77/Software77) has shutdown, there's a new data provider - more accurate and more durable: me!
- Improved internal IP detection: support for cloud load balancers.
- Improved IP data download process.
- Improved data signature verification.

### Fixed
- Erroneous message when it's not possible to download data signature.

## [2.2.0] - 2021-02-24

### Added
- Compatibility with WordPress 5.7.

### Changed
- Consistent reset for settings.
- Improved translation loading.
- [WP_CLI] `location` command have now a definition and all synopsis are up to date.

### Fixed
- In Site Health section, Opcache status may be wrong (or generates PHP warnings) if OPcache API usage is restricted.

## [2.1.0] - 2020-11-23

### Added
- Compatibility with WordPress 5.6.

### Changed
- Improvement in the way roles are detected.

### Fixed
- [SEC001] User may be wrongly detected in XML-RPC or Rest API calls.
- When site is in english and a user choose another language for herself/himself, menu may be stuck in english.
- [WP-CLI] Typos in `status` command result.

## [2.0.0] - 2020-10-15

### Added
- Full analytics dashboard with detailed accesses, countries and languages (see "Locations" in PerfOps Analytics menu).
- New tool (in PerfOps Tools menu) to analyze an IP address.
- [WP-CLI] New command to get a location detail: see `wp help location describe` for details.
- [WP-CLI] New command to toggle on/off main settings: see `wp help location settings` for details.
- [WP-CLI] New command to display IP Locator status: see `wp help location status` for details.
- [WP-CLI] New command to display locations statistics: see `wp help location analytics` for details.
- New Site Health "info" section about shared memory.
- [API] New `/wp-json/ip-locator/v1/describe` endpoint to analyze an IP. Available to all authenticated users.
- Compatibility with WordPress 5.5.
- [MultiSite] New menu to get Locations right from "my sites" or network admin page.
- Support for data feeds - reserved for future use.

### Changed
- Improvement in the way roles are detected.
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins (thanks to [Loïc Antignac](https://github.com/webaxones)).
- [MultiSite] Improved default site detection.
- Improved layout for language indicator.
- Admin notices are now set to "don't display" by default.
- Improved IP detection  (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- Improved changelog readability.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.
- A warning is now displayed in the settings page when no device detection mechanism is found.
- Prepares PerfOps menus to future 5.6 version of WordPress.

### Fixed
- The remote IP can be wrongly detected when behind some types of reverse-proxies.
- When trying to detect local network IPs, an (innocuous) error `Cannot load resource element und_xx: U_MISSING_RESOURCE_ERROR` may be shown in logs.
- With Firefox, some links are unclickable in the Control Center (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).
- Some internal logs are wrongly classified as 'INFO' when it should be 'DEBUG'.
- Some typos in `CHANGELOG.md`.

### Removed
- Parsedown as integrated markdown parser.
- The section "Current connexion" of the data tab, as it is now in tools.

## [1.0.6] - 2020-06-29

### Changed
- Detection of improper PHP intl module installation.
- Full compatibility with PHP 7.4.
- Automatic switching between memory and transient when a cache plugin is installed without a properly configured Redis / Memcached.

### Fixed
- When used for the first time, settings checkboxes may remain checked after being unchecked.

## [1.0.5] - 2020-05-05

### Fixed
- There's an error while activating the plugin when the server is Microsoft IIS with Windows 10.
- With Microsoft Edge, some layouts may be ugly.

## [1.0.4] - 2020-04-18

### Changed
- [MultiSite] Only main site is now responsible for updating data, to avoid quota overrun.

## [1.0.3] - 2020-04-10

### Fixed
- Some main settings may be not saved.

### Removed
- A link to a non-existent page.

## [1.0.2] - 2020-04-07

### Changed
- Improved initialization and update of IP data.
- Improves error handling when downloading IP data files.

### Fixed
- With some old versions of DB servers, tables can't be created.
- Some typos in the settings screen.

## [1.0.1] - 2020-04-02

Initial release
