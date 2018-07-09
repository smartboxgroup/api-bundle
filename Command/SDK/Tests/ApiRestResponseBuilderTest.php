<?php

namespace Smartbox\ApiBundle\Tests\SDK;

use Guzzle\Http\Message\Response;
use JMS\Serializer\SerializerBuilder;
use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestResponse;
use Smartbox\ApiRestClient\ApiRestResponseBuilder;
use Smartbox\ApiRestClient\Tests\Fixture\Entity\Product;

class ApiRestResponseBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyResponse()
    {
        $guzzleResponse = new Response('200');
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, null);
        $this->assertNotNull($response);
        $this->assertEquals('200', $response->getStatusCode());
    }

    public function testRandomHeadersResponse()
    {
        $headers = array(
            'status' => 'accepted',
        );

        $guzzleResponse = new Response('200', $headers);
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, null);
        $this->assertNotNull($response);
        $actualHeaders = $response->getHeaders();
        $this->assertEquals($headers, $actualHeaders);
        $this->assertEquals('accepted', $actualHeaders['status']);
    }

    public function testCorrectHeadersResponse()
    {
        $headers = array(
            ApiRestResponse::RATE_LIMIT_LIMIT => 'rateLimitLimit',
            ApiRestResponse::RATE_LIMIT_REMAINING => 'rateLimitRemaining',
            ApiRestResponse::RATE_LIMIT_RESET_REMAINING => 'rateLimitResetRemaining',
            ApiRestResponse::RATE_LIMIT_RESET => 'rateLimitReset',
        );

        $guzzleResponse = new Response('200', $headers);
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, null);
        $this->assertNotNull($response);
        $this->assertSame($headers, $response->getHeaders());
        $this->assertEquals('rateLimitLimit', $response->getRateLimitLimit());
        $this->assertEquals('rateLimitReset', $response->getRateLimitReset());
        $this->assertEquals('rateLimitRemaining', $response->getRateLimitRemaining());
        $this->assertEquals('rateLimitResetRemaining', $response->getRateLimitResetRemaining());
    }

    public function testStringBodyResponse()
    {
        $guzzleResponse = new Response('200', array(), 'string');
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, null);
        $this->assertNotNull($response);
        $this->assertEquals('string', $response->getBody());
        $this->assertEquals('string', $response->getRawBody());
    }

    public function testObjectBodyResponse()
    {
        $product = new Product();
        $product->setType(Product::TYPE_BOX);
        $product->setId('id');

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($product, ApiRestInternalClient::FORMAT_JSON);

        $guzzleResponse = new Response('200', array(), $jsonContent);
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, 'Smartbox\ApiRestClient\Tests\Fixture\Entity\Product');
        $this->assertNotNull($response);
        $this->assertEquals($product, $response->getBody());
    }

    public function testArrayObjectBodyResponse()
    {
        $product1 = new Product();
        $product1->setType(Product::TYPE_BOX);
        $product1->setId('id1');

        $product2 = new Product();
        $product2->setType(Product::TYPE_BOX);
        $product2->setId('id2');

        $products = array($product1, $product2);

        $serializer = SerializerBuilder::create()->build();
        $jsonContent = $serializer->serialize($products, ApiRestInternalClient::FORMAT_JSON);

        $guzzleResponse = new Response('200', array(), $jsonContent);
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, 'array<Smartbox\ApiRestClient\Tests\Fixture\Entity\Product>');
        $this->assertNotNull($response);
        $this->assertEquals($products, $response->getBody());
        $this->assertEquals($jsonContent, $response->getRawBody());
    }
}
