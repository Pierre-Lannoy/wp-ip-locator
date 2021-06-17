# IP Locator shortcodes
With IP Locator, you can output the following items via shortcodes:

1. [IP address](#ip-address)
2. [Country code](#country-code)
3. [Country name](#country-name)
4. [Country flag](#country-flag)
5. [Language name](#language-name)
6. [Conditional shortcode](#conditional-shortcode)
6. [Notes](#notes)

## IP address
To render the IP address detected by IP Locator, use the following shortcode:
```
  [iplocator-ip]
```

## Country code
To render the country code detected by IP Locator, use the following shortcode:
```
  [iplocator-code]
```

## Country name
To render the country name detected by IP Locator, use the following shortcode:
```
  [iplocator-country language=""]
```
The `language` parameter can be omitted. If so, the country name will be outputted in the visitor language. Otherwise you can specify the following values:
- `self`: renders the country name in the main language of the country;
- a locale id (like `en` or `fr_CA`): renders the country name in the specified language.

Example, if the detected country is Sweden (country code `SE`):

- `[iplocator-country]` outputs `Швеция` if the user language is `ru_RU`, 
- `[iplocator-country language="self"]` outputs `Švedska` 
- `[iplocator-country language="fr"]` outputs `Suède`

## Country flag
To render the flag of the country detected by IP Locator, use the following shortcode:
```
  [iplocator-flag type="" class="" style="" id="" alt=""]
```
If you omit all parameters, the flag will be rendered as an emoji but, if you want to render it as an image you have to specify the type as follow:
- `"image"`: the flag with a 4:3 w/h ratio
- `"squared-image"`: the flag with a 1:1 w/h ratio

To control how the image is rendered, you can specify the following attributes of the image tag:
- `class`: the css class name(s), for example `"my-image-class"`
- `style`: the css style, for example `"width:20px;float:left;"`
- `id`: the css id, for example `"my-image-id"`
- `alt`: the alternative text, for example `"this is a country flagr"`

## Language name
IP Locator tries, for each detected country, to "infer" its main language. It isn't an "error-proof" method (as many countries have more than one official language), but it gives significantly good results. To render this language name, use the following shortcode:
```
  [iplocator-lang language=""]
```
The `language` parameter can be omitted. If so, the language name will be outputted in the visitor language. Otherwise you can specify the following values:
- `self`: renders the language name in its own language;
- a locale id (like `en` or `fr_CA`): renders the language name in the specified language.

Example, if the detected language is Swedish (country code `SE`):

- `[iplocator-lang]` outputs `sueco` if the user language is `es_ES`, 
- `[iplocator-lang language="self"]` outputs `svenska` 
- `[iplocator-lang language="vi"]` outputs `Thụy Điển`

## Conditional shortcode
You can choose to show or hide something, regarding the detected country and/or language. To do so, use the following shortcode:
```
  [iplocator-if country="" not-country="" lang="" not-lang="" do=""] A string or a shortcode [/iplocator-if]
```
Where `do` can be `"show"` (to display "A string or a shortcode") or `"hide"` (to not display "A string or a shortcode").

The operators `country`, `not-country`, `lang` and `not-lang` may contain one or more parameters (comma separated) and are cumulative (ie. you can use several of them). You can use any lang identifier or country code.

Note shortcodes can be nested only if they have not the same name (that's a WordPress limitation).

Examples
- `[iplocator-if lang="EN" do="show"] something in english [/iplocator-if]` outputs the string "something in english" only for countries having english as main language, 
- `[iplocator-if country="FR,BE,CA" do="show"] [iplocator-flag] [/iplocator-if]` outputs flags only if detected country is France, Belgium or Canada, 
- `[iplocator-if not-country="00,A1" do="show"] you're identified [/iplocator-if]` outputs the string "you're identified" for everyone except undetected or behind anonymous proxies users,
- `[iplocator-if not-country="FR" not-lang="EN" do="hide"] something [/iplocator-if]` do not output the string "something" for everyone except for French visitors and English-speaking countries ,
- `[iplocator-if country="A0" do="hide"] Hello, stranger! [/iplocator-if]` outputs the string "Hello, stranger!" for everyone who are not on the local network, 

## Notes

### Language
[PHP Intl extension](https://www.php.net/manual/en/intro.intl.php) needs to be activated on your server if you want to use names translation. If this extension is not installed, output of country name and language name will always be in english.

### Country Codes
IP Locator uses the __ISO 3166-1 alpha-2__ standard to handle country codes. This means all [the currently defined country codes](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) are supported.
In addition to these codes, IP Locator uses some special codes to handle specific uses-cases:
- `00`: when IP Locator is definitely unable to detect what is the source IP;
- `01`: when source IP is the _loopback_ IP (`127.0.0.1` and `::1`) which means client and webserver are on the same host;
- `A0`: when the source IP is on a [private range](https://en.wikipedia.org/wiki/Private_network) which means client and webserver are on the same local area network;
- `A1`: when the source IP is unresolvable because the client is behind an anonymous proxy;
- `A2`: when the source IP is from a satellite provider - stratosphere is not a country ;)

Note for the five codes below, the current language is arbitrary set to `en`.

> If you think this documentation is incomplete, not clear, etc. Do not hesitate to open an issue and/or make a pull request.