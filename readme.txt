=== IP Locator ===
Contributors: PierreLannoy
Tags: country, flag, geolocation, language
Requires at least: 5.2
Requires PHP: 7.2
Tested up to: 5.4
Stable tag: 1.0.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Country and language IP-based detection for WordPress. Fast, reliable, plug & play.

== Description ==

**IP Locator** is a country and language detection tool for WordPress. It is fast, reliable and plug & play.

It can detect and render the country, the main language and the country flag of each visitor of your site. It provides:

* a strong, accurate and ultra-fast IP and country detection;
* many shortcodes to display country names, languages and flags (emoji or vectorized);
* a shortcode to conditionally hide or show strings or other shortcodes;
* a CSS modifier to add a country-specific class to the `body` tag of your site;
* an automatic system to be always up to date (no API key, no IP data files to manually import);
* a full-featured API for plugins / themes developers.

For full details, you can browse [the shortcodes list](https://github.com/Pierre-Lannoy/wp-ip-locator/blob/master/SHORTCODES.md) or [the API description](https://github.com/Pierre-Lannoy/wp-ip-locator/blob/master/DEVELOPER.md) (for developers).

Technically, **IP Locator**:

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

> **IP Locator uses IP data from [WebNet77 / Software77](http://software77.net/geo-ip/) which is [donationware](http://software77.net/geo-ip/?license). If you find this data useful, consider making a donation - even a small one - to help keep it free.**
> **IP Locator accesses this service on a regular basis (if the option is checked) to maintain an up-to-date version of the data. By using IP Locator, you agree with the [Software77 license](http://software77.net/geo-ip/?license)**

**IP Locator**  is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

= Developers =

If you're a plugins / themes developer and want to take advantage of the detection features of IP Locator, visit the [GitHub repository](https://github.com/Pierre-Lannoy/wp-ip-locator) of the plugin to learn how to use it.

= Support =

This plugin is free and provided without warranty of any kind. Use it at your own risk, I'm not responsible for any improper use of this plugin, nor for any damage it might cause to your site. Always backup all your data before installing a new plugin.

Anyway, I'll be glad to help you if you encounter issues when using this plugin. Just use the support section of this plugin page.

= Privacy =

This plugin, as any piece of software, is neither compliant nor non-compliant with privacy laws and regulations. It is your responsibility to use it - by activating the corresponding options or services - with respect for the personal data of your users and applicable laws.

This plugin doesn't set any cookie in the user's browser.

This plugin may handle personally identifiable information (PII). If the GDPR or CCPA or similar regulation applies to your case, you must adapt your processes (consent management, security measure, treatment register, etc.).

= Donation =

If you like this plugin or find it useful and want to thank me for the work done, please consider making a donation to [La Quadrature Du Net](https://www.laquadrature.net/en) or the [Electronic Frontier Foundation](https://www.eff.org/) which are advocacy groups defending the rights and freedoms of citizens on the Internet. By supporting them, you help the daily actions they perform to defend our fundamental freedoms!

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'.
2. Search for 'IP Locator'.
3. Click on the 'Install Now' button.
4. Activate IP Locator.

= From WordPress.org =

1. Download IP Locator.
2. Upload the `ip-locator` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...).
3. Activate IP Locator from your Plugins page.

= Once Activated =

1. Visit 'Settings > IP Locator' in the left-hand menu of your WP Admin to adjust settings.
2. Enjoy!

== Frequently Asked Questions ==

= What are the requirements for this plugin to work? =

You need at least **WordPress 5.2** and **PHP 7.2**.

= Can this plugin work on multisite? =

Yes. It is designed to work on multisite too. Network Admins can configure the plugin. All sites users can use plugin features (shortcodes and APIs).

= Where can I get support? =

Support is provided via the official [WordPress page](https://wordpress.org/support/plugin/ip-locator/).

= Where can I find documentation? =

Developer's documentation can be found in the [GitHub repository](https://github.com/Pierre-Lannoy/wp-ip-locator) of the plugin.

= Where can I report a bug? =
 
You can report bugs and suggest ideas via the [GitHub issue tracker](https://github.com/Pierre-Lannoy/wp-ip-locator/issues) of the plugin.

== Changelog ==

Please, see [full changelog](https://github.com/Pierre-Lannoy/wp-ip-locator/blob/master/CHANGELOG.md) on GitHub.

== Upgrade Notice ==

== Screenshots ==

