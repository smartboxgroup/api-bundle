<?php

namespace Smartbox\ApiRestClient;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;

/**
 * Class ApiRestInternalClient.
 */
class ApiRestInternalClient
{
    const FORMAT_JSON           = 'json';

    const HTTP_METHOD_GET       = 'GET';
    const HTTP_METHOD_POST      = 'POST';
    const HTTP_METHOD_PUT       = 'PUT';
    const HTTP_METHOD_PATCH     = 'PATCH';
    const HTTP_METHOD_DELETE    = 'DELETE';

    /** @var \GuzzleHttp\Client */
    protected $client;

    /** @var string */
    protected $baseUrl;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /**
     * Return the available.
     *
     * @return array
     */
    public static function getAvailableHttpMethod()
    {
        return [
            self::HTTP_METHOD_DELETE,
            self::HTTP_METHOD_GET,
            self::HTTP_METHOD_POST,
            self::HTTP_METHOD_PUT,
            self::HTTP_METHOD_PATCH,
        ];
    }

    /**
     * @param $username
     * @param $password
     * @param $baseUrl
     */
    public function __construct($username, $password, $baseUrl)
    {
        $this->client = new Client();

        $this->password = $password;
        $this->username = $username;
        $this->baseUrl = $baseUrl;
    }

    /**
     * @param $method
     * @param $uri
     * @param null $object
     * @param array $filters
     * @param array $headers
     * @param string $serializationType
     *
     * @return ApiRestResponse
     * @throws \Exception
     */
    public function request($method, $uri, $object = null, array $filters = [], array $headers = [], $serializationType = null)
    {
        if (!in_array($method, self::getAvailableHttpMethod())) {
            throw new \Exception("Unknown HTTP method $method");
        }

        $request = ApiRestRequestBuilder::buildRequest($this->username, $this->password, $object, $headers, $filters);

        $uri = $this->baseUrl.$uri;

        try {
            /* @var Response*/
            $response = $this->client->request($method, $uri, $request);
        } catch (RequestException $e) {
            throw new \Exception($e);
        }

        return ApiRestResponseBuilder::buildResponse($response, $serializationType);
    }


}
