<?php
namespace Smartbox\ApiRestClient\Tests;

use Smartbox\ApiRestClient\ApiRestRequestBuilder;
use Smartbox\ApiRestClient\Tests\Fixture\Entity\Product;
use Smartbox\ApiRestClient\Tests\Fixture\Entity\Universe;

class ApiRestRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_USERNAME = 'admin';
    const TEST_PASSWORD = 'admin';

    public function testNullBodyRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest("GET", "/", self::TEST_USERNAME, self::TEST_PASSWORD);

        $this->assertEquals(null, $actualRequest->getResponse());
        $this->assertInstanceOf('Guzzle\Http\Message\Request', $actualRequest);
    }

    public function testUrlRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest("GET", "http://example.com", self::TEST_USERNAME, self::TEST_PASSWORD, null, array("header1" => "h1", "header2"=>"h2"));

        $this->assertEquals("http://example.com", $actualRequest->getUrl());
    }


    public function testHeaderRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest("GET", "/", self::TEST_USERNAME, self::TEST_PASSWORD, null, array("header1" => "h1", "header2"=>"h2"));

        $this->assertEquals(array(self::TEST_USERNAME, self::TEST_PASSWORD), $actualRequest->getHeader("auth")->toArray());
        $this->assertEquals(array("application/json"), $actualRequest->getHeader("Content-Type")->toArray());
        $this->assertEquals(array("h1"), $actualRequest->getHeader("header1")->toArray());
        $this->assertEquals(array("h2"), $actualRequest->getHeader("header2")->toArray());
    }

    public function testFilterRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest("GET", "/", self::TEST_USERNAME, self::TEST_PASSWORD, null, array("header1" => "h1", "header2"=>"h2"),array("page"=>"12", ";limit"=>"12"));

        $this->assertEquals(array("h1"), $actualRequest->getHeader("header1")->toArray());
        $this->assertEquals(array("h2"), $actualRequest->getHeader("header2")->toArray());

        $this->assertEquals(array("page"=>"12", ";limit"=>"12"), $actualRequest->getQuery()->getAll());
    }

    public function testEmptyStringRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest("POST", "/", self::TEST_USERNAME, self::TEST_PASSWORD, "");

        $this->assertEquals(null, $actualRequest->getResponse());
    }

    public function testStringRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest("PUT", "/", self::TEST_USERNAME, self::TEST_PASSWORD, "TEST");

        $this->assertEquals(json_encode("TEST"), (string) $actualRequest->getBody());
    }

    public function testBooleanRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest("PUT", "/", self::TEST_USERNAME, self::TEST_PASSWORD, null, array(), array("myBool" => true));

        $this->assertEquals(array("myBool" => "true"), $actualRequest->getQuery()->getAll());
    }

    public function testObjectRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest("POST", "/", self::TEST_USERNAME, self::TEST_PASSWORD, $this->buildProduct("productName", "universeId"));

        $this->assertEquals(json_encode(array("name" => "productName", "universe" => array("id" => "universeId"))), (string) $actualRequest->getBody());
    }

    public function testArrayObjectRequest()
    {
        $products = array(
            $this->buildProduct("productName1", "universeId1"),
            $this->buildProduct("productName2", "universeId2"),
            $this->buildProduct("productName3", "universeId3")
        );

        $actualRequest = ApiRestRequestBuilder::buildRequest("POST", "/", self::TEST_USERNAME, self::TEST_PASSWORD, $products);

        $this->assertEquals(json_encode( array(
            array("name" => "productName1", "universe" => array("id" => "universeId1")),
            array("name" => "productName2", "universe" => array("id" => "universeId2")),
            array("name" => "productName3", "universe" => array("id" => "universeId3"))
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