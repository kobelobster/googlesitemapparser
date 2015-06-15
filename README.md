# Google Sitemap-Parser
An easy-to-use library to parse sitemaps compliant with the Google Standard

## Install

Install via [composer](https://getcomposer.org):

```javascript
{
    "require": {
        "tzfrs/googlesitemapparser": "1.0.1"
    }
}
```

Run `composer install` or `composer update`.

## Getting Started

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use \tzfrs\GoogleSitemapParser;
use \tzfrs\Exceptions\GoogleSitemapParserException;

try {
    $posts = GoogleSitemapParser::parse('http://tzfrs.de/sitemap.xml');
    foreach ($posts as $post) {
        print $post . '<br>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}
```



Contributing is surely allowed! :-)