<?php

namespace Smartbox\ApiBundle\Tests\SDK;

use GuzzleHttp\Psr7\Response;
use JMS\Serializer\SerializerBuilder;
use Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Product;
use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestResponse;
use Smartbox\ApiRestClient\ApiRestResponseBuilder;

class ApiRestResponseBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyResponse()
    {
        $guzzleResponse = new Response("200");
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, null);
        $this->assertNotNull($response);
        $this->assertEquals("200", $response->getStatusCode());
    }

    public function testRandomHeadersResponse()
    {
        $headers = array(
            "status" => "accepted",
        );

        $guzzleResponse = new Response("200", $headers);
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, null);
        $this->assertNotNull($response);
        $this->assertEquals($headers, $response->getHeaders());
        $this->assertEquals("accepted", $response->getHeaders()["status"]);
    }

    public function testCorrectHeadersResponse()
    {
        $headers = array(
            ApiRestResponse::RATE_LIMIT_LIMIT => "rateLimitLimit",
            ApiRestResponse::RATE_LIMIT_REMAINING => "rateLimitRemaining",
            ApiRestResponse::RATE_LIMIT_RESET_REMAINING => "rateLimitResetRemaining",
            ApiRestResponse::RATE_LIMIT_RESET => "rateLimitReset",
            ApiRestResponse::TRANSACTION_ID => "transID"
        );

        $guzzleResponse = new Response("200", $headers);
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, null);
        $this->assertNotNull($response);
        $this->assertEquals($headers, $response->getHeaders());
        $this->assertEquals("transID", $response->getTransactionId());
        $this->assertEquals("rateLimitLimit", $response->getRateLimitLimit());
        $this->assertEquals("rateLimitReset", $response->getRateLimitReset());
        $this->assertEquals("rateLimitRemaining", $response->getRateLimitRemaining());
        $this->assertEquals("rateLimitResetRemaining", $response->getRateLimitResetRemaining());
    }

    public function testStringBodyResponse()
    {
        $guzzleResponse = new Response("200", [], "string");
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, null);
        $this->assertNotNull($response);
        $this->assertEquals("string", $response->getBody());
    }

    public function testObjectBodyResponse()
    {
        $product = new Product();
        $product->setType(Product::TYPE_BOX);
        $product->setId("id");

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($product, ApiRestInternalClient::FORMAT_JSON);

        $guzzleResponse = new Response("200", [], $jsonContent);
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, "Smartbox\\ApiBundle\\Tests\\SDK\\Fixture\\Entity\\Product");
        $this->assertNotNull($response);
        $this->assertEquals($product, $response->getBody());
    }

    public function testArrayObjectBodyResponse()
    {
        $product1 = new Product();
        $product1->setType(Product::TYPE_BOX);
        $product1->setId("id1");

        $product2 = new Product();
        $product2->setType(Product::TYPE_BOX);
        $product2->setId("id2");

        $products = [$product1, $product2];

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($products, ApiRestInternalClient::FORMAT_JSON);

        $guzzleResponse = new Response("200", [], $jsonContent);
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, "array<Smartbox\\ApiBundle\\Tests\\SDK\\Fixture\\Entity\\Product>");
        $this->assertNotNull($response);
        $this->assertEquals($products, $response->getBody());
    }


}