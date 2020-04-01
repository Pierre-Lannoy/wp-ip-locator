# IP Locator shortcodes

IP Locator uses the __ISO 3166-1 alpha-2__ standard to handle country codes. This means all [current country codes](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) are supported.

In addition to these codes, IP Locator adds some special codes to handle specific uses-cases:
- `00`: when IP Locator is definitely unable to detect what is the source IP;
- `01`: when source IP is the _loopback_ IP (`127.0.0.1` and `::1`) which means client and webserver are on the same host;
- `A0`: when the source IP is on a [private range](https://en.wikipedia.org/wiki/Private_network) which means client and webserver are on the same local area network;
- `A1`: when the source IP is unresolvable because the client is behind an anonymous proxy;
- `A2`: when the source IP is from a satellite provider - stratosphere is not a country ;)
