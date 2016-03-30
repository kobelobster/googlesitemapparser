# Google Sitemap-Parser
An easy-to-use library to parse sitemaps compliant with the Google Standard

## Install

Install via [composer](https://getcomposer.org):

```json
{
    "require": {
        "tzfrs/googlesitemapparser": "2.0.*"
    }
}
```

Run `composer install` or `composer update`.

## Features
#### Basic parsing
Parses sitemap URLs of your choice. Supports `.xml`, `.xml.gz` and plain text.
#### Parsing from robots.txt
Searches for Sitemap entries in the robots.txt and parses those files. Also downloads/extracts gzip compressed sitemaps and searches for them


## Getting Started

### Basic parsing
Returns an list of URLs.


```php
use tzfrs\Exceptions\GoogleSitemapParserException;
use tzfrs\GoogleSitemapParser;

try {
    $posts = (new GoogleSitemapParser('http://tzfrs.de/sitemap.xml'))->parse();
    foreach ($posts as $post) {
        print $post . '<br>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}
```

### Advanced parsing
Includes priority and last modified timestamp in the response
To enable this, just set the 2nd parameter of the constructor to true.


```php
use tzfrs\Exceptions\GoogleSitemapParserException;
use tzfrs\GoogleSitemapParser;

try {
    $posts = (new GoogleSitemapParser('http://tzfrs.de/sitemap.xml', true))->parse();
    foreach ($posts as $post) {
        print 'URL: ' . $post['loc'] . '<br>Priority: ' . $post['priority'] . '<br>LastMod: ' . $post['lastmod'] . '<hr>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}
```

### Custom User-Agent
To set any CURL options, including the User-Agent string, just include an array of options in the constructor.

```php
use tzfrs\Exceptions\GoogleSitemapParserException;
use tzfrs\GoogleSitemapParser;

$config = [
    'curl' => [
        CURLOPT_USERAGENT => 'tzfrs/GoogleSitemapParser',
    ]
];

try {
    $posts = (new GoogleSitemapParser('http://tzfrs.de/sitemap.xml', true, $config))->parse();
    foreach ($posts as $post) {
        print 'URL: ' . $post['loc'] . '<br>Priority: ' . $post['priority'] . '<br>LastMod: ' . $post['lastmod'] . '<hr>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}
```


## Methods

- `parse`

Contributing is surely allowed! :-)

See the file `LICENSE` for licensing information
