<?php
/**
 * COPClient
 * PHP version 5
 *
 * @category Class
 * @package  COP\Client
 * @author   jinyicheng
 * @link     https://github.com/jinyicheng/cop
 */

namespace COP\Client;

use GuzzleHttp\Middleware;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * COPClient
 *
 * @category Class
 * @package  COP\Client
 * @author   jinyicheng
 * @link     https://github.com/jinyicheng/cop
 */
class COPClient
{
    /**
     * COPClient version
     *
     * @var string
     */
    const VERSION = "0.2.0";
    /**
     * COP Supported HTTP Version
     */
    const HTTP_VERSION = "1.1";
    /**
     * COP API domain
     *
     * @var string
     */
    const  API_DOMAINS = [
        'api.lines.coscoshipping.com',
        'api-pp.lines.coscoshipping.com'
    ];

    /**
     * COP API base urls
     *
     * @var array of string
     */
    protected static $BASE_URLS = [
        '/service/',
        '/service/v3/'
    ];

    /**
     * Merchant credentials
     *
     * @var Credentials
     */
    protected $credentials;

    /**
     * Response Validator
     *
     *  @var Validator
     */
    protected $validator;
    /**
     * COP Base URI
     * @var String
     */
    protected $copBaseUri;

    /**
     * Constructor
     */
    public function __construct(Credentials $credentials, Validator $validator)
    {
        $this->credentials = $credentials;
        $this->validator = $validator;
    }
    /**
     * Specify COP base uri.
     * 
     */
    public function withCopBaseUri($copBaseUri) {
        //Check copBaseUri
        $uri = new \GuzzleHttp\Psr7\Uri($copBaseUri."/");
        if(! self::isCOPApiUrl($uri)) {
            throw new \InvalidArgumentException('COPClient: Unsupported copBaseUri - '.$copBaseUri);
        }

        $this->copBaseUri = $copBaseUri;
        return $this;
    }

    public function getCopBaseUri() {
        return $this->copBaseUri;
    }
    protected ?\GuzzleHttp\HandlerStack $stack = NULL;
    /**
     * Unbind original HandlerStack.
     */
    protected function removeHandlerStack() {
        if(isset($this->stack) && $this->stack !== '' && $this->stack !== NULL ) {
            $this->stack->remove("cop.request.inteceptor001");
            $this->stack->remove("cop.request.inteceptor002");
            $this->stack->remove("cop.response.inteceptor001");
        }
        return $this;
    }
    public function getHttpHandlerStack():\GuzzleHttp\HandlerStack {
        return $this->stack;
    }
    /**
     * Sets \GuzzleHttp\HandlerStack $stack
     * @param \GuzzleHttp\HandlerStack $stack
     * @return $this
     */
    public function withHttpHandlerStack(\GuzzleHttp\HandlerStack $stack) {
        $this->removeHandlerStack();
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {

            if (!self::isCOPApiUrl($request->getUri())) {
                return $request;
            }

            //Checking HTTP Version
            if($request->getProtocolVersion()!==self::HTTP_VERSION) {
                throw new \UnexpectedValueException("Unsupported HTTP version:".$request->getProtocolVersion().". Only HTTP/1.1 supported.");
            }
            
            if (self::isUserAgentOverwritable($request)) {
                $request = $request->withHeader('User-Agent', self::getUserAgent());
            }
            if (!$request->getBody()->isSeekable() && \class_exists("\\GuzzleHttp\\Psr7\\CachingStream")) {
                $request = $request->withBody(new \GuzzleHttp\Psr7\CachingStream($request->getBody()));
            }
            return $request;
        }),"cop.request.inteceptor001");

        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $this->credentials->sign($request);
        }),"cop.request.inteceptor002");

        
        $stack->push(Middleware::mapResponse(function (ResponseInterface $response) {

            $code = $response->getStatusCode();
            if ($code >= 200 && $code < 300) {
                if (!$response->getBody()->isSeekable() && \class_exists("\\GuzzleHttp\\Psr7\\CachingStream")) {
                    $response = $response->withBody(new \GuzzleHttp\Psr7\CachingStream($response->getBody()));
                }
            }
            return $response;
        }),"cop.response.inteceptor001");
        $this->stack = $stack;
        return $this;
    }
    /**
     * Create a new builder
     *
     * @return COPClientBuilder
     */
    public static function builder($apiKey = '', $secretKey = '') {

        $builder = new COPClientBuilder();
        if($apiKey !=='' &&  $secretKey !=='') {
            $builder = $builder->withAuthentication($apiKey, $secretKey);
        }
        
        return $builder;
    }
    /**
     * Check whether url is COP API url
     */
    protected static function isCOPApiUrl(UriInterface $url)
    {
        if ($url->getScheme() !== 'https' || !\in_array($url->getHost(), self::API_DOMAINS )) {
            return false;
        }
        foreach (self::$BASE_URLS as $baseUrl) {
            if (\substr($url->getPath(), 0, strlen($baseUrl)) === $baseUrl) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get User Agent
     * @return string
     */
    protected static function getUserAgent()
    {
        static $userAgent = '';
        if (!$userAgent) {
            $agent = 'COP-Guzzle/'.self::VERSION;
            if (\class_exists('\\GuzzleHttp\\Client')) {
                $version = defined('\\GuzzleHttp\\Client::VERSION') ? \GuzzleHttp\Client::VERSION
                                                                    : \GuzzleHttp\Client::MAJOR_VERSION;
                $agent .= ' GuzzleHttp/'.$version;
            }
            if (extension_loaded('curl') && function_exists('curl_version')) {
                $agent .= ' curl/'.\curl_version()['version'];
            }
            $agent .= \sprintf(" (%s/%s) PHP/%s", PHP_OS, \php_uname('r'), PHP_VERSION);
            $userAgent = $agent;
        }
        return $userAgent;
    }

    private static function isUserAgentOverwritable(RequestInterface $request)
    {
        if (!$request->hasHeader('User-Agent')) {
            return true;
        }
        $headers = $request->getHeader('User-Agent');
        $userAgent = $headers[\count($headers) - 1];
        if (\function_exists('\\GuzzleHttp\\default_user_agent')) {
            return $userAgent === \GuzzleHttp\default_user_agent();
        }
        return false;
    }
    protected ?\GuzzleHttp\Client $httpClient = NULL;
    /**
     * Allocate a \GuzzleHttp\Client instance.
     * @return \GuzzleHttp\Client
     */
    public function getGuzzleHttpClient(array $config = []): \GuzzleHttp\Client
    {
        if (!isset($config['handler'])) {
            $config['handler'] = $this->stack;
        } elseif (!\is_callable($config['handler'])) {
            throw new \InvalidArgumentException('handler must be a callable');
        }
        if($this->httpClient === NULL) {
            $this->httpClient= new \GuzzleHttp\Client($config);
        }
        return $this->httpClient;
    }
}
