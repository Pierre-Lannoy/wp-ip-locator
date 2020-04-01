# IP Locator shortcodes

You can

1. [IP address](#ip-address)
2. [Country code](#country-code)
3. [Country name](#country-name)
4. [Country flag](#country-flag)
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
The `language`[[1](#notes)] parameter can be omitted. If so, the country name will be outputted in the visitor language. Otherwise you can specify the following values:
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

To control how the image is rendered, you cans specify the following attributes of the image tag:
- `class`: the css class name(s), for example `"my-image-class"`
- `style`: the css style, for example `"width:20px;float:left;"`
- `id`: the css id, for example `"my-image-id"`
- `alt`: the alternative text, for example `"flag country for the visitor"`

## Language name

IP Locator tries, for each detected country, to "infer" its main language. It isn't an "error-proof" method (as many countries have more than one official language), but it gives significantly good results. To render this language name, use the following shortcode:
```
    [iplocator-lang language=""]
```
The `language`[[1](#notes)] parameter can be omitted. If so, the language name will be outputted in the visitor language. Otherwise you can specify the following values:
- `self`: renders the language name in its own language;
- a locale id (like `en` or `fr_CA`): renders the language name in the specified language.

Example, if the detected language is Swedish (country code `SE`):

- `[iplocator-country]` outputs `sueco` if the user language is `es_ES`, 
- `[iplocator-country language="self"]` outputs `svenska` 
- `[iplocator-country language="vi"]` outputs `Thụy Điển` 



#### Notes
##### [1] Language
[PHP Intl extension](https://www.php.net/manual/en/intro.intl.php) needs to be activated on your server if you want to use names translation. If this extension is not installed, output of country name and language name will always be in english.




> If you think this documentation is incomplete, not clear, etc. Do not hesitate to open an issue and/or make a pull request.