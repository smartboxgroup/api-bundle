<?php

namespace Smartbox\ApiRestClient\Tests\Fixture;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Product;
use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestResponse;

/**
 * Class MockApiRestInternalClient
 * Class used to simulate a generated client.
 */
class MockApiRestInternalClient extends ApiRestInternalClient
{
    public static $class = 'Smartbox\ApiRestClient\Tests\Fixture\MockApiRestInternalClient';

    /**
     * MockApiRestInternalClient constructor.
     *
     * @param $username
     * @param $password
     * @param $baseUrl
     * @param array $responses
     * @param array $exceptions
     */
    public function __construct($username, $password, $baseUrl, $responses = array(), $exceptions = array())
    {
        $mockHandler = new MockHandler($responses);
        $handler = HandlerStack::create($mockHandler);
        $httpClient = new Client([
            'handler' => $handler,
        ]);
        $this->client = $httpClient;
        $this->password = $password;
        $this->username = $username;
        $this->baseUrl = $baseUrl;
    }

    /**
     * @param Product $entity
     * @param array   $headers
     *
     * @return ApiRestResponse
     */
    public function sendProductCreate(Product $entity, array $headers = array())
    {
        $uri = '/product_create';

        return $this->request('POST', $uri, $entity, array(), $headers, 'Smartbox\ApiBundle\SDK\Tests\Fixture\Entity\Product');
    }

    /**
     * @param Product $entity
     * @param array   $headers
     *
     * @return ApiRestResponse
     */
    public function sendProductConfirmation(Product $entity, array $headers = array())
    {
        $uri = '/product_confirmation';

        return $this->request('POST', $uri, $entity, array(), $headers, null);
    }
}