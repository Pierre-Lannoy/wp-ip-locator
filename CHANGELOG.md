# Changelog
All notable changes to **IP Locator** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **IP Locator** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
