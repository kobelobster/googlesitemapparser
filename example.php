<?php
require __DIR__ . '/vendor/autoload.php';

use \tzfrs\GoogleSitemapParser;
use \tzfrs\Exceptions\GoogleSitemapParserException;

try {
    $posts = (new GoogleSitemapParser('http://tzfrs.de/sitemap.xml'))->parse();
    foreach ($posts as $post) {
        print $post . '<br>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}