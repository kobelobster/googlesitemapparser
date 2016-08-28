<?php namespace tzfrs;

use SimpleXMLElement;
use jyggen\Curl;
use jyggen\Curl\Request;
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
     * Whether the priority of the sitemap entry should be also gathered
     * @var bool
     */
    protected $includeInformation = false;
    /**
     * The constructor checks if the SimpleXML Extension is loaded and afterwards sets the URL to parse
     *
     * @param string $url The URL of the Sitemap
     * @param bool $includeInformation Whether the priority of the sitemap entry should be also gathered
     * @throws GoogleSitemapParserException
     */
    public function __construct($url, $includeInformation = false)
    {
        if (!extension_loaded('simplexml')) {
            throw new GoogleSitemapParserException('The extension `simplexml` must be installed and loaded for this library');
        }
        $this->url                  = $url;
        $this->includeInformation   = $includeInformation;
    }

    /**
     * This method reads in the json-decoded XML String from the page and analyzes it. It checks whether
     * the URLs in the sitemap are posts or links to a sub-sitemap. Dependent on that the method then reads in the
     * sitemap urls
     * @param string $url Optional parameter when not wanting to use the current set URL
     * @return \Generator
     * @throws GoogleSitemapParserException
     */
    public function parse($url = null)
    {
        $url        = ($url === null) ? $this->url : $url;
        $response   = $this->getContent($url);
        return $this->parseFromXMLString($response);
    }

    public function parseFromRobots($url = null)
    {
        $url = ($url === null) ? $this->url : $url;
        $response = $this->getContent($url);
        preg_match_all('#Sitemap:\s*(.*)#', $response, $matches);
        if (isset($matches[1])) {
            foreach ($matches[1] as $sitemap) {
                if (substr($sitemap, -3) === "xml") {
                    foreach ($this->parse($sitemap) as $key=>$subPost) {
                        yield $key=>$subPost;
                    }
                } elseif (substr($sitemap, -6) === 'xml.gz') {
                    foreach ($this->parseFromXMLString($this->downloadAndExtractGZIP($sitemap)) as $key=>$subPost) {
                        yield $key=>$subPost;
                    }
                }
            }
        }
    }

    public function parseFromXMLString($string)
    {
        /** @var bool|SimpleXMLElement $sitemapJson */
        $sitemapJson   = $this->validateXML($string);
        if ($sitemapJson === false && empty($string)) {
            throw new GoogleSitemapParserException('The XML found on the given URL doesn\'t appear to be valid according to simplexml_load_string/libxml');
        }

        if (isset($sitemapJson->sitemap)) {
            foreach ($sitemapJson->sitemap as $post) {
                if (substr($post->loc, -3) === "xml") {
                    foreach ($this->parse((string)$post->loc) as $subPost) {
                        yield $subPost;
                    }
                } elseif (substr($post->loc, -6) === 'xml.gz') {
                    foreach ($this->parseCompressed((string)$post->loc) as $subPost) {
                        yield $subPost;
                    }
                }
            }
        } elseif (isset($sitemapJson->url)) {
            foreach ($sitemapJson->url as $url) {
                if ($this->includeInformation) {
                    yield (string)$url->loc => [
                        'priority'  => (string)$url->priority,
                        'lastmod'   => (string)$url->lastmod,
                    ];
                } else {
                    yield (string)$url->loc;
                }
            }
        } else {
            $offset = 0;
            while (preg_match('/(\S+)/', $string, $match, PREG_OFFSET_CAPTURE, $offset)) {
                $offset = $match[0][1] + strlen($match[0][0]);
                yield $match[0][0];
            }
        }
    }

    /**
     * Method used to parse compressed sitemaps such as example.com/sitemap.xml.gz
     *
     * @param string|null $url
     * @return \Generator
     * @throws GoogleSitemapParserException
     */
    public function parseCompressed($url = null)
    {
        $url = ($url === null) ? $this->url : $url;
        foreach ($this->parseFromXMLString($this->downloadAndExtractGZIP($url)) as $key=>$subPost) {
            yield $key=>$subPost;
        }
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

    /**
     * @param string $url The URL of the gzip
     * @return string the uncompressed downloaded content
     * @throws Curl\Exception\ProtectedOptionException
     * @throws GoogleSitemapParserException
     */
    protected function downloadAndExtractGZIP($url)
    {
        try {
            $request = new Request($url);
            $request->setOption(CURLOPT_ENCODING, '');
            $request->execute();
            /** @var \Symfony\Component\HttpFoundation\Response $response */
            $response = $request->getResponse();
            return $response->headers->get('Content-Type') === 'application/xml'
                ? $response->getContent()
                : gzdecode($response->getContent());
        } catch (Curl\Exception\CurlErrorException $e) {
            throw new GoogleSitemapParserException($e->getMessage());
        }
    }


    /**
     * Returns the content of a page
     * @param string $url the URL that should be gathered
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws GoogleSitemapParserException
     */
    protected function getContent($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new GoogleSitemapParserException('Passed URL not valid according to filter_var function');
        }
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = Curl::get($url)[0];
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            throw new GoogleSitemapParserException('The server responds with a bad status code: '. $response->getStatusCode());
        }
        return $response->getContent();
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
     * Setter for the includePriority variable. Used to modify if the response should contain includePriority
     *
     * @param bool $includeInformation Whether the priority of the sitemap entry should be also gathered
     * @return $this Returns itself
     */
    public function setIncludeInformation($includeInformation)
    {
        $this->includeInformation = $includeInformation;
        return $this;
    }
}
