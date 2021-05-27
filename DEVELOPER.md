# Developing with IP Locator

Before starting to explain how to use IP Locator from a developer point of view, I would like to thank you to take the time to invest your knowledge and skills in making IP Locator better and more useful. I'll only have one word: you rock! (OK, that's two words)

Now, what's the menu today?

1. [What is IP Locator?](#what-is-ip-locator)
2. [IP Locator REST API](#ip-locator-rest-api)
3. [IP Locator PHP API](#ip-locator-php-api)
    - [IP and IP detection](#ip-and-ip-detection)
    - [Country codes and names](#country-codes-and-names)
    - [Language codes and names](#language-codes-and-names)
    - [Emoji flags](#emoji-flags)
    - [Vectorized flags](#vectorized-flags)
4. [Contribution Guidelines](/CONTRIBUTING.md)
5. [Code of Conduct](/CODE_OF_CONDUCT.md)

## What is IP Locator?
IP Locator is mainly a tool to analyze remote IP from which a WordPress site is accessed. It has the same pros and cons as all other tools using remote IPs: it is operational for every web server, there's no need of javascript from the client-side, but think about it as something "fakable" or "maskable". It can not be seen as something 100% reliable.

IP Locator, once activated, is ready to be queried via some simple API calls. When you use this API, you don't have to worry about detection and cache management. You can call this API as many times as you want without any performance impact and you can do it as soon as the `init` hook is executed.

## IP Locator REST API
IP Locator has a single endpoint which accepts `GET` requests from all authenticated users: `/wp-json/ip-locator/v3/describe`.

This endpoint accepts 2 parameters:
* `ip` - mandatory: to specify the IP address (IPv4 or IPv6) to analyze.
* `locale` - optional: to specify the locale (like `"en"` or `"fr_CA"`) in which render the result.

### Example
```console
pierre@dev:~$ curl --location --request GET '.../wp-json/ip-locator/v3/describe?ip=8.8.4.4&locale=fr_FR' --header '...'
{"ip":"8.8.4.4","country":{"code":"US","name":"\u00c9tats-Unis"},"language":{"code":"en","name":"anglais"},"flag":{"square":"data:image\/svg+xml;base64,PHN2Zy...z4K","rectangle":"data:image\/svg+xml;base64,PHN2Zy...z4K","emoji":"\ud83c\uddfa\ud83c\uddf8"}}
```

## IP Locator PHP API
This API is callable in procedural style or O-O style. It's up to you to choose your prefered way to use it. There's no feature or performance difference between these two styles. 

### IP and IP detection

IP Locator API lets you set the IP you want to analyze by yourself. But, if you want IP Locator detect the IP of the current request, just set this IP as `null` in all API calls like that:
```php
<?php
    // O-O Style
    // Echoes the detected IP.
    $country = new IPLocator\API\Country();
    echo $country->source();
    
    // Procedural Style
    // Echoes the detected IP.
    echo iplocator_get_ip();
```
As previously seen, you can "force" the IP to analyze:
```php
<?php
    // O-O Style
    // Echoes the forced IP.
    $country = new IPLocator\API\Country( '192.18.19.20' );
    echo $country->source();
    
    // Procedural Style
    // Echoes the forced IP.
    echo iplocator_get_ip( '192.18.19.20' );
```

### Country codes and names
To get the [country code](/COUNTRYCODES.md) or name from the IP address it's, once again, very simple: 
```php
<?php
    // O-O Style
    // Echoes the country code & name.
    $country = new IPLocator\API\Country();
    echo $country->code();
    echo $country->name();
    
    // Procedural Style
    // Echoes the country code & name.
    echo iplocator_get_country_code();
    echo iplocator_get_country_name();
```
Note `IPLocator\API\Country::name()` and `iplocator_get_country_name` can be called with an optional `$lang` parameter. Accepted values for this parameter are:
- `"self"` to render the country name in the main language of the country;
- a locale id (like `"en"` or `"fr_CA"`) to render the country name in the specified language.

Example:
```php
<?php
    // O-O Style
    // Echoes the country french name.
    $country = new IPLocator\API\Country();
    echo $country->name( 'fr' );
    
    // Procedural Style
    // Echoes the country vietnamese name.
    echo iplocator_get_country_name( 'vi' );
```
If no parameter is provided, the display language is set to the current viewer language (based on WordPress user's settings or website settings). 

### Language codes and names
To get the main country language code or name from the IP address just do it like that: 
```php
<?php
    // O-O Style
    // Echoes the language code & name.
    $country = new IPLocator\API\Country();
    echo $country->lang()->code();
    echo $country->lang()->name();
    
    // Procedural Style
    // Echoes the language code & name.
    echo iplocator_get_language_code();
    echo iplocator_get_language_name();
```
Note `IPLocator\API\Lang::name()` and `iplocator_get_language_name` can be called with an optional `$lang` parameter. Accepted values for this parameter are:
- `"self"` to render the language name in its own language;
- a locale id (like `"en"` or `"fr_CA"`) to render the language name in the specified language.

Example:
```php
<?php
    // O-O Style
    // Echoes the language french name.
    $country = new IPLocator\API\Country();
    echo $country->lang()->name( 'fr' );
    
    // Procedural Style
    // Echoes the language vietnamese name.
    echo iplocator_get_language_name( 'vi' );
```
If no parameter is provided, the display language is set to the current viewer language (based on WordPress user's settings or website settings). 

### Emoji flags
To get the country flag as emoji it's, again, very simple: 
```php
<?php
    // O-O Style
    // Echoes the flag as emoji.
    $country = new IPLocator\API\Country();
    echo $country->flag()->emoji();
    
    // Procedural Style
    // Echoes the flag as emoji.
    echo iplocator_get_flag_emoji();
```

### Vectorized flags
To get the country as a full `img` HTML tag with a base 64 encoded inline SVG, you can use :
```php
<?php
    // O-O Style
    // Echoes the flag as image tag.
    $country = new IPLocator\API\Country();
    echo $country->flag()->image();
    
    // Procedural Style
    // Echoes the flag as image tag.
    echo iplocator_get_flag_image();
```
To control how the image is rendered, you can specify the following attributes of the image tag:
- `$class` (string): the css class name(s), for example `"my-image-class"`
- `$style` (string): the css style, for example `"width:20px;float:left;"`
- `$id` (string): the css id, for example `"my-image-id"`
- `$alt` (string): the alternative text, for example `"this is a country flag"`
- `$squared` (boolean): is the image should be 1:1 w/h ratio (otherwise it is 4:3 w/h ratio).

Example:
```php
<?php
    // O-O Style
    // Echoes the flag as image tag, with specific attributes.
    $country = new IPLocator\API\Country();
    echo $country->flag()->image( 'my-image-class', 'width:20px;float:left;', 'my-image-id', 'this is a country flag', true );
    
    // Procedural Style
    // Echoes the flag as image tag, with specific attributes.
    echo iplocator_get_flag_image( 'my-image-class', 'width:20px;float:left;', 'my-image-id', 'this is a country flag', true );
```

> If you think this documentation is incomplete, not clear, etc. Do not hesitate to open an issue and/or make a pull request.
