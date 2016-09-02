<?php

namespace Smartbox\ApiBundle\Tests\SDK\Fixture;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Product;
use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestResponse;


class MockApiRestInternalClient extends ApiRestInternalClient
{

    public function __construct($username, $password, $baseUrl, $responses = [])
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $this->client = new Client(['handler' => $handler]);

        $this->password = $password;
        $this->username = $username;
        $this->baseUrl = $baseUrl;
    }

    /**
     * @param Product $entity
     * @param array $headers
     *
     * @return ApiRestResponse
     */
    public function sendProductCreate(Product $entity, array $headers = array())
    {

        $uri = '/product_create';
        return $this->request('POST', $uri, $entity, array(), $headers, 'Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Product');
    }

    /**
     * @param Product $entity
     * @param array $headers
     *
     * @return ApiRestResponse
     */
    public function sendProductConfirmation(Product $entity, array $headers = array())
    {
        $uri = '/product_confirmation';
        return $this->request('POST', $uri, $entity, array(), $headers, null);
    }

    /**
     * @param null $object
     * @param array $headers
     * @param array $filters
     *
     * @return array
     */
    public function buildRequest ($object = null, $headers = [], $filters = [])
    {
        return parent::buildRequest($object);
    }
}