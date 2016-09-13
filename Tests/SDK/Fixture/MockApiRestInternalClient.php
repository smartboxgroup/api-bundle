<?php

namespace Smartbox\ApiBundle\Tests\SDK\Fixture;


use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;
use Guzzle\Plugin\Mock\MockPlugin;
use Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Product;
use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestResponse;

/**
 * Class MockApiRestInternalClient
 * Class used to simulate a generated client
 *
 * @package Smartbox\ApiBundle\Tests\SDK\Fixture
 */
class MockApiRestInternalClient extends ApiRestInternalClient
{

    /**
     * MockApiRestInternalClient constructor.
     *
     * @param $username
     * @param $password
     * @param $baseUrl
     * @param array $responses
     */
    public function __construct($username, $password, $baseUrl, $responses = array())
    {
        $mock = new MockPlugin();
        foreach ($responses as $response){
            $mock->addResponse($response);
        }

        $this->subscribers = array($mock);
        $this->client = new Client();

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

        return $this->request('POST', $uri, $entity, [], $headers, null);
    }
}