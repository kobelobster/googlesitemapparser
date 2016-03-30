# Google Sitemap-Parser
An easy-to-use library to parse sitemaps compliant with the Google Standard

## Installation
The library is available for install via Composer package. To install, add the requirement to your `composer.json` file, like this:

```json
{
    "require": {
        "tzfrs/googlesitemapparser": "2.0.*"
    }
}
```

Then run `composer update`.

Find out more about Composer here: [https://getcomposer.org](https://getcomposer.org):

## Features
#### Sitemap
Parses sitemap URLs of your choice. Supports `.xml`, `.xml.gz` and plain text.
#### robots.txt
Searches for Sitemap entries in the robots.txt and parses those files.


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
Includes priority and last modified timestamp in the response.

To enable this, just set the 2nd parameter of the constructor to true.


```php
use tzfrs\Exceptions\GoogleSitemapParserException;
use tzfrs\GoogleSitemapParser;

try {
    $parser = new GoogleSitemapParser('http://tzfrs.de/sitemap.xml');
    $parser->returnTags(true);
    $posts = $parser->parse();
    foreach ($posts as $post) {
        print 'URL: ' . $post['loc'] . '<br>Priority: ' . $post['priority'] . '<br>LastMod: ' . $post['lastmod'] . '<hr>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}
```

### Custom User-Agent string
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
    $parser = new GoogleSitemapParser('http://tzfrs.de/sitemap.xml', $config);
    $parser->returnTags(true);
    $posts = $parser->parse();
    foreach ($posts as $post) {
        print 'URL: ' . $post['loc'] . '<br>Priority: ' . $post['priority'] . '<br>LastMod: ' . $post['lastmod'] . '<hr>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}
```



Contributing is surely allowed! :-)

See the file `LICENSE` for licensing information
