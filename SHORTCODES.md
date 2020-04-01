# IP Locator shortcodes

You can

1. [IP address](#ip-address)
2. [Country code](#country-code)
3. [Country name](#country-name)
5. [Language name](#language-name)


## IP address

To render the IP address detected by IP Locator, use the following shortcode:
```
    [iplocator-ip]
```

## Country code

To render the [country code](/COUNTRYCODES.md) detected by IP Locator, use the following shortcode:
```
    [iplocator-code]
```

## Country name

To render the country name detected by IP Locator, use the following shortcode:
```
    [iplocator-country language=""]
```
The `language`[[1](#requirements)] parameter can be omitted. If so, the country name will be outputted in the visitor language. Otherwise you can specify the following values:
- `self`: renders the country name in the main language of the country;
- a locale id (like `en` or `fr_CA`): renders the country name in the specified language.

Example, if the detected country is Sweden (country code `SE`):

- `[iplocator-country]` outputs `Швеция` if the user language is `ru_RU`, 
- `[iplocator-country language="self"]` outputs `Švedska` 
- `[iplocator-country language="fr"]` outputs `Suède` 

## Language name

IP Locator tries, for each detected country, to 
To render the country name detected by IP Locator, use the following shortcode:
```
    [iplocator-lang language=""]
```
The `language`[[1](#requirements)] parameter can be omitted. If so, the language name will be outputted in the visitor language. Otherwise you can specify the following values:
- `self`: renders the language name in its own language;
- a locale id (like `en` or `fr_CA`): renders the language name in the specified language.

Example, if the detected language is Swedish (country code `SE`):

- `[iplocator-country]` outputs `Sueco` if the user language is `es_ES`, 
- `[iplocator-country language="self"]` outputs `svenska` 
- `[iplocator-country language="vi"]` outputs `Thụy Điển` 



### Requirements

#### [1] Language
[PHP Intl extension](https://www.php.net/manual/en/intro.intl.php) needs to be activated on your server if you want to use names translation. If this extension is not installed, output of country name and language name will always be in english.




> If you think this documentation is incomplete, not clear, etc. Do not hesitate to open an issue and/or make a pull request.