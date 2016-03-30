<?php
require __DIR__ . '/../vendor/autoload.php';

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
