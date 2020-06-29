# Changelog
All notable changes to **IP Locator** is documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **IP Locator** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased - will be 1.0.6]
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
### Initial release
