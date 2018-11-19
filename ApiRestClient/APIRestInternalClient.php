<?php

namespace Smartbox\ApiRestClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;

/**
 * Class ApiRestInternalClient.
 */
class ApiRestInternalClient
{
    public static $class = 'Smartbox\ApiRestClient\ApiRestInternalClient';

    const FORMAT_JSON = 'json';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';
    const HTTP_METHOD_PATCH = 'PATCH';
    const HTTP_METHOD_DELETE = 'DELETE';

    /** @var Client */
    protected $client;

    /** @var string */
    protected $baseUrl;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var EventSubscriberInterface */
    protected $subscribers;

    /**
     * Return the available.
     *
     * @return array
     */
    public static function getAvailableHttpMethod()
    {
        return array(
            self::HTTP_METHOD_DELETE,
            self::HTTP_METHOD_GET,
            self::HTTP_METHOD_POST,
            self::HTTP_METHOD_PUT,
            self::HTTP_METHOD_PATCH,
        );
    }

    /**
     * ApiRestInternalClient constructor.
     *
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
     * Send a request to a given URI.
     *
     * @param $method
     * @param $path
     * @param null   $object
     * @param array  $filters
     * @param array  $headers
     * @param string $deserializationType
     *
     * @return ApiRestResponse
     *
     * @throws \Exception
     */
    public function request($method, $path, $object = null, array $filters = array(), array $headers = array(), $deserializationType = null)
    {
        if (!in_array($method, self::getAvailableHttpMethod())) {
            throw new \Exception("Unknown HTTP method $method");
        }

        $uri = $this->baseUrl.$path;

        $request = ApiRestRequestBuilder::buildRequest($method, $uri, $this->username, $this->password, $object, $headers, $filters);

        if (!empty($this->subscribers)) {
            foreach ($this->subscribers as $subscriber) {
                $request->addSubscriber($subscriber);
            }
        }

        try {
            /* @var Response*/
            $response = $this->client->send($request);
        } catch (RequestException $e) {
            $errorResponse = $e->getResponse();
            if (!empty($errorResponse)) {
                $response = ApiRestResponseBuilder::buildResponse($errorResponse);
                throw new ApiRestException($response, $e);
            } else {
                throw $e;
            }
        }

        return ApiRestResponseBuilder::buildResponse($response, $deserializationType);
    }
}
