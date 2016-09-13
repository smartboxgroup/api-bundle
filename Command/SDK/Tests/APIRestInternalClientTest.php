<?php

namespace Smartbox\ApiBundle\Tests\SDK;

use Guzzle\Http\Message\Response;
use JMS\Serializer\SerializerBuilder;
use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestResponse;
use Smartbox\ApiRestClient\Tests\Fixture\MockApiRestInternalClient;
use Smartbox\ApiRestClient\Tests\Fixture\Entity\Product;

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

        $response =  new Response( 200, array(), $jsonContent);
        $client = $this->getClient(array($response));


        $otherProduct = new Product();
        $otherProduct->setName("name");
        $response = $client->request('POST', "/createProduct", $otherProduct, array(), array(), 'Smartbox\ApiRestClient\Tests\Fixture\Entity\Product');

        $this->assertInstanceOf(ApiRestResponse::$class, $response);
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
        $jsonContent = $serializer->serialize(array($product1, $product2), ApiRestInternalClient::FORMAT_JSON);

        $headers = array(
            ApiRestResponse::RATE_LIMIT_LIMIT => "rateLimitLimit",
            ApiRestResponse::RATE_LIMIT_REMAINING => "rateLimitRemaining",
            ApiRestResponse::RATE_LIMIT_RESET_REMAINING => "rateLimitResetRemaining",
            ApiRestResponse::RATE_LIMIT_RESET => "rateLimitReset",
            "x-transaction-id" => "42"
        );

        $client = $this->getClient(array(new Response(200, $headers, $jsonContent )));

        $response = $client->request('GET', "/products", null, array(), array(), 'array<Smartbox\ApiRestClient\Tests\Fixture\Entity\Product>');

        $this->assertInstanceOf(ApiRestResponse::$class, $response);

        $this->assertEquals(2, count($response->getBody()));
        $body = $response->getBody();
        $this->assertEquals("id1", $body[0]->getId());
        $this->assertEquals("id2", $body[1]->getId());

        $headers = $response->getHeaders();
        $this->assertEquals("42", $headers ["x-transaction-id"]);
        $this->assertEquals("rateLimitReset", $headers [ApiRestResponse::RATE_LIMIT_RESET]);
        $this->assertEquals("rateLimitResetRemaining", $headers [ApiRestResponse::RATE_LIMIT_RESET_REMAINING]);
        $this->assertEquals("rateLimitRemaining", $headers [ApiRestResponse::RATE_LIMIT_REMAINING]);
        $this->assertEquals("rateLimitLimit", $headers [ApiRestResponse::RATE_LIMIT_LIMIT]);

    }
}
