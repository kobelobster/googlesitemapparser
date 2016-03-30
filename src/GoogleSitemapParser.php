<?php
namespace tzfrs;

use jyggen\Curl;
use jyggen\Curl\Request;
use SimpleXMLElement;
use tzfrs\Exceptions\GoogleSitemapParserException;

/**
 * Class GoogleSitemapParser
 * @package tzfrs
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
    protected $asArray = false;

    /**
     * Configuration options
     * @var array
     */
    protected $config = [];

    /**
     * The constructor checks if the SimpleXML Extension is loaded and afterwards sets the URL to parse
     *
     * @param string $url The URL of the Sitemap
     * @param bool $asArray Whether the priority of the sitemap entry should be also gathered
     * @param array $config Configuration options
     * @throws GoogleSitemapParserException
     */
    public function __construct($url, $asArray = false, $config = [])
    {
        if (!extension_loaded('simplexml')) {
            throw new GoogleSitemapParserException('The extension `simplexml` must be installed and loaded for this library');
        }

        mb_language("uni");
        @mb_internal_encoding('UTF-8');

        $this->url = $url;
        $this->asArray = $asArray;
        $this->config = $config;
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
        $url = ($url === null) ? $this->url : $url;
        $response = $this->getContent($url);
        if (mb_strpos($response, "\x1f" . "\x8b" . "\x08", 0, "US-ASCII") === 0) {
            $response = gzdecode($response);
        }
        if (parse_url($url, PHP_URL_PATH) == '/robots.txt') {
            return $this->parseRobotstxt($response);
        }
        return $this->parseXML($response);
    }

    /**
     * Returns the content of a page
     * @param string $url the URL that should be gathered
     * @return mixed raw URL content
     * @throws GoogleSitemapParserException
     */
    protected function getContent($url)
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->curlResponse($url);
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            throw new GoogleSitemapParserException('The server responds with a bad status code: ' . $response->getStatusCode());
        }
        return $response->getContent();
    }

    /**
     * Get URL response from CURL
     *
     * @param string $url The url to request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws GoogleSitemapParserException
     */
    protected function curlResponse($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new GoogleSitemapParserException('Passed URL not valid according to filter_var function');
        }
        try {
            $request = new Request($url);
            $request->setOption(CURLOPT_ENCODING, '');
            // Apply custom options
            if (isset($this->config['curl'])) {
                foreach ($this->config['curl'] as $key => $value) {
                    $request->setOption($key, $value);
                }
            }
            $request->execute();
            return $request->getResponse();
        } catch (Curl\Exception\CurlErrorException $e) {
            throw new GoogleSitemapParserException($e->getMessage());
        }
    }

    /**
     * Search for sitemaps in the robots.txt content
     *
     * @param string $content robots.txt content
     * @return \Generator
     */
    protected function parseRobotstxt($content)
    {
        preg_match_all('#Sitemap:\s*(.*)#', $content, $matchatches);
        if (isset($matchatches[1])) {
            foreach ($matchatches[1] as $sitemap) {
                if ($this->isSitemapURL($sitemap)) {
                    foreach ($this->parse($sitemap) as $key => $subPost) {
                        yield $key => $subPost;
                    }
                }
            }
        }
    }

    /**
     * Sitemap URL filter
     *
     * @param string $url
     * @return bool
     */
    protected function isSitemapURL($url)
    {
        return is_string($url) && (
            substr($url, -4) === ".xml" ||
            substr($url, -7) === '.xml.gz'
        );
    }

    /**
     * Parse XML content
     *
     * @param string $xml XML content
     * @return \Generator
     * @throws GoogleSitemapParserException
     */
    public function parseXML($xml)
    {
        /** @var bool|SimpleXMLElement $sitemapJson */
        $sitemapJson = $this->validateXML($xml);
        if ($sitemapJson === false && empty($xml)) {
            throw new GoogleSitemapParserException('The XML found on the given URL doesn\'t appear to be valid according to simplexml_load_string/libxml');
        }

        if (isset($sitemapJson->sitemap)) {
            foreach ($sitemapJson->sitemap as $post) {
                if ($this->isSitemapURL($post->loc)) {
                    foreach ($this->parse($post->loc) as $subPost) {
                        yield $subPost;
                    }
                }
            }
        } elseif (isset($sitemapJson->url)) {
            foreach ($sitemapJson->url as $url) {
                if ($this->asArray) {
                    yield (string)$url->loc => (array)$url;
                } else {
                    yield (string)$url->loc;
                }
            }
        } else {
            $offset = 0;
            while (preg_match('/(\S+)/', $xml, $match, PREG_OFFSET_CAPTURE, $offset)) {
                $offset = $match[0][1] + strlen($match[0][0]);
                yield $match[0][0];
            }
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
     * Setter for the asArray variable. Used to modify if the response should contain Priority, Last modified time, etc
     *
     * @param bool $asArray Whether the priority of the sitemap entry should be also gathered
     * @return $this Returns itself
     */
    public function setAsArray($asArray)
    {
        $this->asArray = $asArray;
        return $this;
    }
}
