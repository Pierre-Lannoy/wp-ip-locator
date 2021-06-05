IP Locator is fully usable from command-line, thanks to [WP-CLI](https://wp-cli.org/). You can set IP Locator options and much more, without using a web browser.

1. [Obtaining statistics about locations](#obtaining-statistics-about-locations) - `wp location analytics`
2. [Describing a location](#describing-a-location) - `wp location describe`
3. [Getting IP Locator status](#getting-ip-locator-status) - `wp location status`
4. [Managing main settings](#managing-main-settings) - `wp location settings`
5. [Misc flags](#misc-flags)

## Obtaining statistics about locations

You can get locations analytics for today (compared with yesterday). To do that, use the `wp location analytics` command.

If you're in a multisite environment, use `--site=<site_id>` to specify which site you want to get. 

By default, the outputted format is a simple table. If you want to customize the format, just use `--format=<format>`. Note if you choose `json` or `yaml` as format, the output will contain full data and metadata for the current day.

### Examples

To display locations statistics for site id 5, type the following command:
```console
pierre@dev:~$ wp location analytics --site=5
+-----------+----------------------------------------+-------+--------+-----------+
| kpi       | description                            | value | ratio  | variation |
+-----------+----------------------------------------+-------+--------+-----------+
| Countries | Accessing countries.                   | 2     | -      | -93.55%   |
| Languages | Main languages of accessing countries. | 2     | -      | -90%      |
| Public    | Hits from public IPs.                  | 134.0 | 10.23% | -87.79%   |
| Local     | Hits from private IPs.                 | 1.1K  | 87.63% | +445.53%  |
| Satellite | Hits from satellite IPs.               | 0.0   | 0%     | 0%        |
| Detection | Hits from detected IPs.                | 1.3K  | 97.86% | -1.95%    |
+-----------+----------------------------------------+-------+--------+-----------+
```

## Describing a location

To obtain the detail of a location, based on its IP address, use the `wp location describe <ip>` command, where `<ip>` is a IPv4 or IPv6 address.

By default, the outputted format is a simple table. If you want to customize the format, just use `--format=<format>`. Note if you choose `json` or `yaml` as format, the output will contain full data and metadata regarding the location.

### Examples

To display the detail of a location for "12.8.5.9" address , type the following command:
```console
pierre@dev:~$ wp location describe 12.8.5.9
 +---------------+---------------+
 | key           | value         |
 +---------------+---------------+
 | ip            | 12.8.5.9      |
 | country_code  | US            |
 | country_name  | United States |
 | language_code | en            |
 | language_name | English       |
 | flag_emoji    | 🇺🇸             |
 +---------------+---------------+
```

## Getting IP Locator status

To get detailed status and operation mode, use the `wp location status` command.

## Managing main settings

To toggle on/off main settings, use `wp location settings <enable|disable> <analytics|metrics>`.

If you try to disable a setting, wp-cli will ask you to confirm. To force answer to yes without prompting, just use `--yes`.

### Available settings

- `analytics`: analytics feature
- `metrics`: metrics collation feature

### Example

To disable analytics without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp location settings disable analytics --yes
Success: analytics are now deactivated.
```

## Misc flags

For most commands, IP Locator lets you use the following flags:
- `--yes`: automatically answer "yes" when a question is prompted during the command execution.
- `--stdout`: outputs a clean STDOUT string so you can pipe or store result of command execution.

> It's not mandatory to use `--stdout` when using `--format=count` or `--format=ids`: in such cases `--stdout` is assumed.

> Note IP Locator sets exit code so you can use `$?` to write scripts.
> To know the meaning of IP Locator exit codes, just use the command `wp location exitcode list`.