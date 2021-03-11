<?php
/**
 * COPClient
 * PHP version 7
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
class Client
{
    private $secretKey = '';
    private $apiKey = '';
    private $baseUri = '';
    private $apiRequestHeaders = [];

    public function __construct()
    {
        $environment = env('cop.' . env('cop.development'));
        $this->setApiKey($environment['api_key']);
        $this->setSecretKey($environment['secret_key']);
        $this->setBaseUri($environment['base_uri']);
    }

    /**
     * 获取请求头
     * @return array
     */
    public function getApiRequestHeaders(): array
    {
        return $this->apiRequestHeaders;
    }

    /**
     * 生成请求头
     * @param string $method
     * @param string $request_uri
     * @param string $body
     */
    private function setApiRequestHeaders(string $method, string $request_uri, string $body): void
    {
        $md5Guid = md5($this->getGuid());
        $xCosconDate = date(DATE_RFC7231, time());
        $xCosconDigest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));
        $requestLine = $method . ' ' . $request_uri . ' HTTP/1.1';
        $encodedSignature = base64_encode(hash_hmac('sha1', "X-Coscon-Date: " . $xCosconDate . "\nX-Coscon-Digest: " . $xCosconDigest . "\nX-Coscon-Content-Md5: " . $md5Guid . "\n" . $requestLine, $this->getSecretKey(), true));
        $xCosconAuthorization = "hmac username=\"" . $this->getApiKey() . "\",algorithm=\"" . "hmac-sha1" . "\",headers=\"X-Coscon-Date X-Coscon-Digest X-Coscon-Content-Md5 request-line\",signature=\"" . $encodedSignature . "\"";
        $this->apiRequestHeaders = [
            'X-Coscon-Date' => $xCosconDate,
            'X-Coscon-Digest' => $xCosconDigest,
            'X-Coscon-Content-Md5' => $md5Guid,
            'X-Coscon-Authorization' => $xCosconAuthorization,
            'X-Coscon-Hmac' => $md5Guid,
            'content-type' => 'application/json'
        ];
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * @param string $secretKey
     */
    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * @param string $baseUri
     */
    public function setBaseUri(string $baseUri): void
    {
        $this->baseUri = $baseUri;
    }

    /**
     * 获取唯一ID
     * @return string
     */
    private function getGuid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125);// "}"
        return $uuid;
    }

    /**
     * 发送请求
     * @param string $method
     * @param string $requestUri
     * @param array $body
     * @return StreamInterface
     * @throws GuzzleException
     */
    private function request(string $method, string $requestUri, array $body)
    {
        $encodedBody = json_encode($body, JSON_UNESCAPED_UNICODE);
        $this->setApiRequestHeaders($method, $requestUri, $encodedBody);
        $client = new Client([
            'base_uri' => $this->getBaseUri()
        ]);
        return $client->request($method, $requestUri, [
            'body' => $encodedBody,
            'headers' => $this->getApiRequestHeaders()
        ])->getBody();

    }

    /**
     * 获取城市ID
     * @param string $cityName 港口城市名称
     * @return int
     */
    public function findCityId(string $cityName):int
    {
        return $this->selectCity($cityName)['data']['content'][0]['city']['id'];
    }

    /**
     * 港口城市查询
     * @param string $cityName 港口城市名称
     * @return array
     */
    public function selectCity(string $cityName): array
    {
        return json_decode($this->request('POST', '/service/synconhub/common/port/search', [
            "keywords" => $cityName,
            "page" => 1,
            "size" => 30
        ]), true);
    }

    /**
     * 产品列表
     * @param string $fndCityName 目的港口城市名称
     * @param string $porCityName 起运港口城市名称
     * @param string $startDate 开航时间范围开始
     * @param string $endDate 开航时间范围截止
     * @return array
     */
    public function selectProduct(string $fndCityName, string $porCityName, string $startDate, string $endDate): array
    {
        $data = [
            "startDate" => date("Y-m-d\TH:i:s.v\Z", strtotime($startDate)),
            "endDate" => date("Y-m-d\TH:i:s.v\Z", strtotime($endDate)),
            "fndCityId" => (int)$this->findCityId($fndCityName),
            "porCityId" => (int)$this->findCityId($porCityName),
            "page" => 1,
            "size" => 20
        ];
        return json_decode($this->request('POST', '/service/synconhub/product/instantBooking/search', $data), true);
    }

    /**
     * 获取产品详情
     * @param $productId
     * @return array
     */
    public function findProduct(int $productId): array
    {
        return json_decode($this->request('GET', '/service/synconhub/product/instantBooking/' . $productId, []), true);
    }

    /**
     * 订舱
     * @param array $data 预定信息
     * @return array
     */
    public function shipmentBooking(array $data): array
    {
        return json_decode($this->request('POST', '/service/synconhub/shipment/booking', $data), true);
    }
}
