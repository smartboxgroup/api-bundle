<?php

namespace Smartbox\ApiBundle\Tests\SDK;

use GuzzleHttp\Psr7\Response;
use JMS\Serializer\SerializerBuilder;
use Smartbox\ApiRestClient\ApiRestException;
use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestResponse;
use Smartbox\ApiRestClient\Tests\Fixture\Entity\Product;
use Smartbox\ApiRestClient\Tests\Fixture\MockApiRestInternalClient;

/**
 * Class ApiRestInternalClientTest.
 */
class ApiRestInternalClientTest extends \PHPUnit\Framework\TestCase
{
    const TEST_USERNAME = 'admin';
    const TEST_PASSWORD = 'admin';

    /**
     * @return MockApiRestInternalClient
     */
    public function getClient(array $responses = [], array $exceptions = [])
    {
        return new MockApiRestInternalClient(self::TEST_USERNAME, self::TEST_USERNAME, 'http://example.com/', $responses, $exceptions);
    }

    public function testMethodWithOneSerializedObjectInResponse()
    {
        $product = new Product();
        $product->setId('42');

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($product, ApiRestInternalClient::FORMAT_JSON);

        $response = new Response(200, [], $jsonContent);
        $client = $this->getClient([$response]);

        $otherProduct = new Product();
        $otherProduct->setName('name');
        $response = $client->request('POST', '/createProduct', $otherProduct, [], [], 'Smartbox\ApiRestClient\Tests\Fixture\Entity\Product');

        $this->assertInstanceOf(ApiRestResponse::$class, $response);
        $this->assertEquals($product, $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('42', $response->getBody()->getId());
    }

    public function testMethodWithArrayObjectInResponse()
    {
        $product1 = new Product();
        $product1->setId('id1');

        $product2 = new Product();
        $product2->setId('id2');

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize([$product1, $product2], ApiRestInternalClient::FORMAT_JSON);

        $headers = [
            ApiRestResponse::RATE_LIMIT_LIMIT => 'rateLimitLimit',
            ApiRestResponse::RATE_LIMIT_REMAINING => 'rateLimitRemaining',
            ApiRestResponse::RATE_LIMIT_RESET_REMAINING => 'rateLimitResetRemaining',
            ApiRestResponse::RATE_LIMIT_RESET => 'rateLimitReset',
            'x-transaction-id' => '42',
        ];

        $client = $this->getClient([new Response(200, $headers, $jsonContent)]);

        $response = $client->request('GET', '/products', null, [], [], 'array<Smartbox\ApiRestClient\Tests\Fixture\Entity\Product>');

        $this->assertInstanceOf(ApiRestResponse::$class, $response);

        $this->assertEquals(2, \count($response->getBody()));
        $body = $response->getBody();
        $this->assertEquals('id1', $body[0]->getId());
        $this->assertEquals('id2', $body[1]->getId());

        $headers = $response->getHeaders();
        $this->assertEquals('42', $headers['x-transaction-id']);
        $this->assertEquals('rateLimitReset', $headers[ApiRestResponse::RATE_LIMIT_RESET]);
        $this->assertEquals('rateLimitResetRemaining', $headers[ApiRestResponse::RATE_LIMIT_RESET_REMAINING]);
        $this->assertEquals('rateLimitRemaining', $headers[ApiRestResponse::RATE_LIMIT_REMAINING]);
        $this->assertEquals('rateLimitLimit', $headers[ApiRestResponse::RATE_LIMIT_LIMIT]);
    }

    /**
     * @expectedException \Exception
     */
    public function testHandleException()
    {
        $client = $this->getClient([new Response(400, ['my_header' => 'value'], 'Bad request')]);

        $client->request('GET', '/products');
    }

    public function testTransformException()
    {
        $client = $this->getClient([new Response(400, ['my_header' => 'value'], 'Bad request')]);

        try {
            $client->request('GET', '/products');
        } catch (ApiRestException $e) {
            $response = $e->getApiRestResponse();
            $headers = $response->getHeaders();
            $this->assertEquals(400, $response->getStatusCode());
            $this->assertEquals('Bad request', $response->getRawBody());
            $this->assertEquals('value', $headers['my_header']);
        }
    }
}
