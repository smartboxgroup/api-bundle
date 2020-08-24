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
    public function __construct($username, $password, $baseUrl, $responses = [], $exceptions = [])
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
     * @return ApiRestResponse
     */
    public function sendProductCreate(Product $entity, array $headers = [])
    {
        $uri = '/product_create';

        return $this->request('POST', $uri, $entity, [], $headers, 'Smartbox\ApiBundle\SDK\Tests\Fixture\Entity\Product');
    }

    /**
     * @return ApiRestResponse
     */
    public function sendProductConfirmation(Product $entity, array $headers = [])
    {
        $uri = '/product_confirmation';

        return $this->request('POST', $uri, $entity, [], $headers, null);
    }
}
