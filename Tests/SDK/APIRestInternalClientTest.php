<?php

namespace Smartbox\ApiBundle\Tests\SDK;

use GuzzleHttp\Psr7\Response;
use JMS\Serializer\SerializerBuilder;
use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestResponse;
use Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Product;
use Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Universe;
use Smartbox\ApiBundle\Tests\SDK\Fixture\MockApiRestInternalClient;

class ApiRestInternalClientTest extends \PHPUnit_Framework_TestCase
{
    const TEST_USERNAME = 'admin';
    const TEST_PASSWORD = 'admin';

    public function getClient(array $responses)
    {
        return new MockApiRestInternalClient(self::TEST_USERNAME, self::TEST_USERNAME, "http://example.com/", $responses);
    }

    public function testMethodWithOneSerializedObjectInResponse()
    {
        $product = new Product();
        $product->setId("42");

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($product, ApiRestInternalClient::FORMAT_JSON);

        $response =  new Response( 200, [], $jsonContent);
        $client = $this->getClient([$response]);

        $otherProduct = new Product();
        $otherProduct->setName("name");
        $response = $client->request('POST', "/createProduct", $otherProduct, array(), array(), 'Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Product');

        $this->assertInstanceOf(ApiRestResponse::class, $response);
        $this->assertEquals($product, $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("42", $response->getBody()->getId());
    }

    public function testMethodWithArrayObjectInResponse()
    {
        $product1 = new Product();
        $product1->setId("id1");

        $product2 = new Product();
        $product2->setId("id2");

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize([$product1, $product2], ApiRestInternalClient::FORMAT_JSON);

        $client = $this->getClient([new Response(200, [], $jsonContent )]);

        $response = $client->request('GET', "/products", null, array(), array(), 'array<Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Product>');

        $this->assertInstanceOf(ApiRestResponse::class, $response);

        $this->assertEquals(2, count($response->getBody()));
        $this->assertEquals("id1", $response->getBody()[0]->getId());
        $this->assertEquals("id2", $response->getBody()[1]->getId());
    }


    public function testMethodEmptyResponse()
    {
        $headers = [
            ApiRestResponse::TRANSACTION_ID => 2515616,
            ApiRestResponse::RATE_LIMIT_RESET => "limitReset",
            ApiRestResponse::RATE_LIMIT_RESET_REMAINING => "limitResetRemaining",
            ApiRestResponse::RATE_LIMIT_LIMIT     => "limitLimit",
            ApiRestResponse::RATE_LIMIT_REMAINING => "limitRemaining"
        ];

        $response = new Response("200", $headers);
        $client = $this->getClient([$response]);
        $entity = new Product();
        $entity->setName("42");

        $response = $client->sendProductConfirmation($entity);

        $this->assertInstanceOf(ApiRestResponse::class, $response);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEmpty($response->getBody());

        $this->assertEquals(2515616, $response->getTransactionId());
        $this->assertEquals("limitReset", $response->getRateLimitReset());
        $this->assertEquals("limitResetRemaining", $response->getRateLimitResetRemaining());
        $this->assertEquals("limitLimit", $response->getRateLimitLimit());
        $this->assertEquals("limitRemaining", $response->getRateLimitRemaining());
    }

}
