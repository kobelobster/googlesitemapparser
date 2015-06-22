<?php
require __DIR__ . '/vendor/autoload.php';

use \tzfrs\GoogleSitemapParser;
use \tzfrs\Exceptions\GoogleSitemapParserException;

try {
    $posts = (new GoogleSitemapParser('http://www.sainsburys.co.uk/robots.txt'))->parseFromRobots();
    foreach ($posts as $post) {
        print 'Post: '. $post . '<br>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}

try {
    $posts = (new GoogleSitemapParser('https://dobre-nemovitosti.cz/sitemap.xml', true))->parseFromRobots();
    foreach ($posts as $post=>$priority) {
        print 'URL: '. $post . '<br>Priority: '. $priority . '<hr>';
    }
} catch (GoogleSitemapParserException $e) {
    print $e->getMessage();
}
