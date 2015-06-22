<?php
require __DIR__ . '/vendor/autoload.php';

use \tzfrs\GoogleSitemapParser;
use \tzfrs\Exceptions\GoogleSitemapParserException;

try {
    $posts = (new GoogleSitemapParser('https://dobre-nemovitosti.cz/sitemap.xml'))->parse();
    foreach ($posts as $post) {
        print 'Post: '. $post . '<br>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}

print '<hr>';

try {
    $posts = (new GoogleSitemapParser('http://www.sainsburys.co.uk/robots.txt', true))->parseFromRobots();
    foreach ($posts as $post=>$information) {
        print 'URL: '. $post . '<br>Information: '. var_export($information, true) . '<hr>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}
