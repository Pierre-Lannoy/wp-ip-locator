# IP Locator
[![version](https://badgen.net/github/release/Pierre-Lannoy/wp-ip-locator/)](https://wordpress.org/plugins/ip-locator/)
[![php](https://badgen.net/badge/php/7.1+/green)](https://wordpress.org/plugins/ip-locator/)
[![wordpress](https://badgen.net/badge/wordpress/5.0+/green)](https://wordpress.org/plugins/ip-locator/)
[![license](https://badgen.net/github/license/Pierre-Lannoy/wp-ip-locator/)](/license.txt)

__IP Locator__ is a country and language detection tool for WordPress. It is fast, reliable and plug & play.

> ⚠️ This plugin is currently waiting wp.org directory approval. It can therefore be subject to major modifications.

See [WordPress directory page](https://wordpress.org/plugins/ip-locator/).

__IP Locator__ can detect and render the country, the main language and the country flag of each visitor of your site. It provides:

* a strong, accurate and ultra-fast IP and country detection;
* many shortcodes to display country names, languages and flags (emoji or vectorized);
* a shortcode to conditionally hide or show strings or other shortcodes;
* a CSS modifier to add a country-specific class to the `body` tag of your site;
* an automatic system to be always up to date (no API key, no IP data files to manually import);
* a full-featured API for plugins / themes developers.

For full details, you can browse [the shortcodes list](https://github.com/Pierre-Lannoy/wp-ip-locator/blob/master/SHORTCODES.md) or [the API description](https://github.com/Pierre-Lannoy/wp-ip-locator/blob/master/DEVELOPER.md) (for developers).

Technically, __IP Locator__:

* works on dedicated or shared servers;
* can detect the source IP even behind proxies or load-balancers;
* can use AWS CloudFront, Cloudflare and Apache mod_geoip to speed-up detection;
* natively supports APCu caching(1) and all other dedicated object caching mechanisms, like Memcached or Redis;
* has a minimal footprint in the page rendering time;
* runs its data updates in background, without impact on the website speed;
* is fully compatible with multisites;
* natively supports names and languages localizations(2).

(1): [APCu](https://www.php.net/manual/en/intro.apcu.php) needs to be activated on your server if you want to use it in IP Locator.

(2): [PHP Intl extension](https://www.php.net/manual/en/intro.intl.php) needs to be activated on your server if you want to use it in IP Locator.

## Installation

### WordPress method (recommended)

1. From your WordPress dashboard, visit _Plugins | Add New_.
2. Search for 'IP Locator'.
3. Click on the 'Install Now' button.

You can now activate __IP Locator__ from your _Plugins_ page.
 
## Contributions

If you find bugs, have good ideas to make this plugin better, you're welcome to submit issues or PRs in this [GitHub repository](https://github.com/Pierre-Lannoy/wp-ip-locator).

Before submitting an issue or a pull request, please read the [contribution guidelines](CONTRIBUTING.md).

> ⚠️ The `master` branch is the current development state of the plugin. If you want a stable, production-ready version, please pick the last official [release](https://github.com/Pierre-Lannoy/wp-ip-locator/releases).

## Smoke tests
[![WP compatibility](https://plugintests.com/plugins/ip-locator/wp-badge.svg)](https://plugintests.com/plugins/ip-locator/latest)
[![PHP compatibility](https://plugintests.com/plugins/ip-locator/php-badge.svg)](https://plugintests.com/plugins/ip-locator/latest)