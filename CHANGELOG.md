# Changelog
All notable changes to **IP Locator** is documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **IP Locator** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased - will be 2.0.0]

### Added
- New Site Health "info" section about shared memory.
- Compatibility with WordPress 5.5.

### Changed
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins (thanks to [Loïc Antignac](https://github.com/webaxones)).
- Improved layout for language indicator.
- Admin notices are now set to "don't display" by default.
- Improved IP detection  (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- Improved changelog readability.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.

### Fixed
- With Firefox, some links are unclickable in the Control Center (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).

### Removed
- Parsedown as integrated markdown parser.

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
