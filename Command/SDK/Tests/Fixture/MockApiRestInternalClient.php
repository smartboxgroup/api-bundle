<?php

namespace Smartbox\ApiRestClient\Tests\Fixture;

use Guzzle\Http\Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Smartbox\ApiRestClient\Tests\Fixture\Entity\Product;
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
        $mock = new MockPlugin();
        foreach ($responses as $response) {
            $mock->addResponse($response);
        }

        foreach ($exceptions as $exception) {
            $mock->addException($exception);
        }

        $this->subscribers = array($mock);
        $this->client = new Client();

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
