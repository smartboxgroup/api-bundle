<?php

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use JMS\Serializer\SerializerBuilder;

/**
 * Class APIRestInternalClient.
 */
class APIRestInternalClient
{
    const FORMAT_JSON = 'json';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';
    const HTTP_METHOD_PATCH = 'PATCH';
    const HTTP_METHOD_DELETE = 'DELETE';

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
     * BifrostSDK constructor.
     *
     * @param $username
     * @param $password
     * @param $baseUrl
     */
    public function __construct($username, $password, $baseUrl)
    {
        $this->client = new \GuzzleHttp\Client();

        $this->password = $password;
        $this->username = $username;
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return [
            'auth' => [
                $this->username,
                $this->password,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];
    }


    /**
     * @param $method
     * @param $uri
     * @param null $object
     * @param array $filters
     * @param array $headers
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function request($method, $uri, $object = null, array $filters = [], array $headers = [])
    {
        if (!in_array($method, self::getAvailableHttpMethod())) {
            throw new \Exception("Unknown HTTP method $method");
        }

        $request = $this->buildRequest($object);

        if (!empty($filters)) {
            $options = ["query" => $filters];
            $request = array_merge($request, $options);
        }

        if (!empty($headers)) {
            $options = ["headers" => $headers];
            $request = array_merge($request, $options);
        }

        $uri = $this->baseUrl.$uri;

        try {
            /* @var Response*/
            $response = $this->client->request($method, $uri, $request);
        } catch (RequestException $e) {
            throw new \Exception($e);
        }

        return $response;
    }

    /**
     * @param null $object
     *
     * @return array
     */
    protected function buildRequest($object = null)
    {
        $jsonContent = '';

        if (!empty($object)) {
            $serializer = SerializerBuilder::create()->build();
            $jsonContent = $serializer->serialize($object, self::FORMAT_JSON);
        }

        $request = array_merge(['body' => $jsonContent], $this->getOptions());

        return $request;
    }
}
