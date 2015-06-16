<?php namespace tzfrs;

use SimpleXMLElement;
use jyggen\Curl;
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
     * The URL that will be parsed
     * @var null|string
     */
    protected $url = null;

    /**
     * The constructor checks if the SimpleXML Extension is loaded and afterwards sets the URL to parse
     *
     * @param string $url The URL of the Sitemap
     * @throws GoogleSitemapParserException
     */
    public function __construct($url)
    {
        if (!extension_loaded('simplexml')) {
            throw new GoogleSitemapParserException('The extension `simplexml` must be installed and loaded for this library');
        }
        $this->url = $url;
    }

    /**
     * This is the main method for the class. It firstly validates the URL and the XML of the URL and then
     * gets the post for the sitemap from the current URL
     *
     * @return array
     * @throws GoogleSitemapParserException
     */
    public function parse()
    {
        return $this->getPosts();
    }

    /**
     * This method reads in the json-decoded XML String from the page and analyzes it. It checks whether
     * the URLs in the sitemap are posts or links to a sub-sitemap. Dependent on that the method then reads in the
     * sitemap urls
     *
     * @throws GoogleSitemapParserException
     */
    protected function getPosts()
    {
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new GoogleSitemapParserException('Passed URL not valid according to filter_var function');
        }
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = Curl::get($this->url)[0];
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            throw new GoogleSitemapParserException('The server responds with a bad status code: '. $response->getStatusCode());
        }
        /** @var bool|SimpleXMLElement $sitemapJson */
        $sitemapJson   = $this->validateXML($response);
        if ($sitemapJson === false) {
            throw new GoogleSitemapParserException('The XML found on the given URL doesn\'t appear to be valid according to simplexml_load_string/libxml');
        }
        if (isset($sitemapJson->sitemap)) {
            foreach ($sitemapJson->sitemap as $post) {
                if (substr($post->loc, -3) === "xml") {
                    $this->setUrl((string)$post->loc);
                    foreach ($this->getPosts() as $subPost) {
                        yield $subPost;
                    }
                }
            }
        } elseif (isset($sitemapJson->url)) {
            foreach ($sitemapJson->url as $url) {
                yield (string)$url->loc;
            }
        }
    }

    /**
     * Setter for the url variable. Used to modify the URL
     *
     * @param string $url The url that should be set
     * @return $this Returns itself
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
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
