<?php

namespace Smartbox\ApiRestClient\Tests;

use GuzzleHttp\Client;
use Smartbox\ApiRestClient\ApiRestRequestBuilder;
use Smartbox\ApiRestClient\Tests\Fixture\Entity\Product;
use Smartbox\ApiRestClient\Tests\Fixture\Entity\Universe;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class ApiRestRequestBuilderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_USERNAME = 'admin';
    const TEST_PASSWORD = 'admin';

    /**
     * The outcome of this function has changed since we update Guzzle to ^6.
     * We do not get a null response as before but instead get an empty string.
     * This test is still valid as it tests what happens when we send null.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testNullBodyRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest('GET', '/', self::TEST_USERNAME, self::TEST_PASSWORD);
        $response = new Response(200, [], null);
        $responses = [$response];
        $mockHandler = new MockHandler($responses);
        $handler = HandlerStack::create($mockHandler);
        $httpClient = new Client([
            'handler' => $handler,
        ]);
        $httpResponse = $httpClient->send($actualRequest);
        $bodyStream = $httpResponse->getBody();
        $this->assertSame('', $bodyStream->getContents());
        $this->assertInstanceOf('GuzzleHttp\Psr7\Request', $actualRequest);
    }

    public function testUrlRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest('GET', 'http://example.com', self::TEST_USERNAME, self::TEST_PASSWORD, null, array('header1' => 'h1', 'header2' => 'h2'));
        $this->assertEquals('http://example.com', (string) $actualRequest->getUri());
    }

    public function testHeaderRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest('GET', '/', self::TEST_USERNAME, self::TEST_PASSWORD, null, array('header1' => 'h1', 'header2' => 'h2'));

        $this->assertEquals(array('Basic '.\base64_encode(self::TEST_USERNAME.':'.self::TEST_PASSWORD)), $actualRequest->getHeader('Authorization'));
        $this->assertEquals(array('application/json'), $actualRequest->getHeader('Content-Type'));
        $this->assertEquals(array('h1'), $actualRequest->getHeader('header1'));
        $this->assertEquals(array('h2'), $actualRequest->getHeader('header2'));
    }

    public function testFilterRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest('GET', '/', self::TEST_USERNAME, self::TEST_PASSWORD, null, array('header1' => 'h1', 'header2' => 'h2'), array('page' => '12', ';limit' => '12'));

        $this->assertEquals(array('h1'), $actualRequest->getHeader('header1'));
        $this->assertEquals(array('h2'), $actualRequest->getHeader('header2'));
        $query = $actualRequest->getUri()->getQuery();
        $result = [];
        \parse_str($query, $result);
        $this->assertEquals(array('page' => '12', ';limit' => '12'), $result);
    }

    public function testEmptyStringRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest('POST', '/', self::TEST_USERNAME, self::TEST_PASSWORD, '');
        $this->assertInstanceOf('GuzzleHttp\Psr7\Request', $actualRequest);
        $response = new Response(200, [], '');
        $responses = array();
        $responses[] = $response;
        $mockHandler = new MockHandler($responses);
        $handler = HandlerStack::create($mockHandler);
        $httpClient = new Client([
            'handler' => $handler,
        ]);
        $httpResponse = $httpClient->send($actualRequest);
        $bodyStream = $httpResponse->getBody();
        $this->assertSame('', $bodyStream->getContents());
    }

    public function testStringRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest('PUT', '/', self::TEST_USERNAME, self::TEST_PASSWORD, 'TEST');

        $this->assertEquals(\json_encode('TEST'), (string) $actualRequest->getBody());
    }

    public function testBooleanRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest('PUT', '/', self::TEST_USERNAME, self::TEST_PASSWORD, null, array(), array('myBool' => true));
        $query = $actualRequest->getUri()->getQuery();
        $result = [];
        \parse_str($query, $result);
        $this->assertEquals(array('myBool' => 'true'), $result);
    }

    public function testObjectRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest('POST', '/', self::TEST_USERNAME, self::TEST_PASSWORD, $this->buildProduct('productName', 'universeId'));

        $this->assertEquals(\json_encode(array('name' => 'productName', 'universe' => array('id' => 'universeId'))), (string) $actualRequest->getBody());
    }

    public function testArrayObjectRequest()
    {
        $products = array(
            $this->buildProduct('productName1', 'universeId1'),
            $this->buildProduct('productName2', 'universeId2'),
            $this->buildProduct('productName3', 'universeId3'),
        );

        $actualRequest = ApiRestRequestBuilder::buildRequest('POST', '/', self::TEST_USERNAME, self::TEST_PASSWORD, $products);

        $this->assertEquals(\json_encode(array(
            array('name' => 'productName1', 'universe' => array('id' => 'universeId1')),
            array('name' => 'productName2', 'universe' => array('id' => 'universeId2')),
            array('name' => 'productName3', 'universe' => array('id' => 'universeId3')),
        )), (string) $actualRequest->getBody());
    }

    protected function buildProduct($productName, $universeId)
    {
        $universe = new Universe();
        $universe->setId($universeId);

        $product = new Product();
        $product->setName($productName);
        $product->setUniverse($universe);

        return $product;
    }
}
