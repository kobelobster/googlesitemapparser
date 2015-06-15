<?php namespace tzfrs;

use SimpleXMLElement;
use Jyggen\Curl\Curl;
use Symfony\Component\HttpFoundation\Response;
use tzfrs\Exceptions\GoogleSitemapParserException;

/**
 * Class GoogleSitemapParser
 * @package tzfrs
 * @version 1.0.1
 * @license MIT License
 *
 * This is the class that handles the parsing of the sitemap
 */
class GoogleSitemapParser
{
    /**
     * This is the main method for the class. It firstly validates the URL and the XML of the URL and then
     * gets the post for the sitemap from the current URL
     *
     * @param string $url The URL of the Sitemap
     * @return array The array with the Posts
     * @throws GoogleSitemapParserException
     */
    public static function parse($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new GoogleSitemapParserException('Passed URL not valid according to filter_var function');
        }
        /** @var Response $response */
        $response = Curl::get($url)[0];
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            throw new GoogleSitemapParserException('The server responds with a bad status code: '. $response->getStatusCode());
        }
        $response   = $response->getContent();
        $gsParser   = new self;
        /** @var bool|SimpleXMLElement $validXML */
        $validXML   = $gsParser->validateXML($response);
        if ($validXML === false) {
            throw new GoogleSitemapParserException('The XML found on the given URL doesn\'t appear to be valid according to ');
        }
        $posts = $gsParser->getPosts($validXML);
        foreach ($posts as $subElement) {
            if (is_array($subElement)) {
                $posts = array_unique(call_user_func_array('array_merge', $posts));
                break;
            }
        }
        return $posts;
    }

    /**
     * This method reads in the json-decoded XML String from the page and analyzes it. It checks whether
     * the URLs in the sitemap are posts or links to a sub-sitemap. Dependent on that the method then reads in the
     * sitemap urls
     *
     * @param SimpleXMLElement $sitemapJson The json-decoded object containing the sitemap information
     * @return array Returns the posts
     * @throws GoogleSitemapParserException
     */
    protected function getPosts(SimpleXMLElement $sitemapJson)
    {
        $posts = [];
        if (isset($sitemapJson->sitemap)) {
            foreach ($sitemapJson->sitemap as $post) {
                if (substr($post->loc, -3) == "xml") {
                    $posts[] = self::parse((string)$post->loc);
                }
            }
        } elseif (isset($sitemapJson->url)) {
            foreach ($sitemapJson->url as $url) {
                $posts[] = (string)$url->loc;
            }
        } else {
            throw new GoogleSitemapParserException('Sitemap has no posts');
        }
        return $posts;
    }

    /**
     * Checks if the XML from the given page is valid or not
     *
     * @param string $xmlstr The XML to be checked
     * @return bool
     */
    protected function validateXML($xmlstr)
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xmlstr);
        if (!$doc) {
            libxml_clear_errors();
            return false;
        }
        return $doc;
    }
}
