<?php

namespace Smartbox\ApiBundle\Tests\SDK;

use Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Product;
use Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Universe;
use Smartbox\ApiRestClient\ApiRestRequestBuilder;

class ApiRestRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_USERNAME = 'admin';
    const TEST_PASSWORD = 'admin';

    public function testBuildNullBodyRequest()
    {

        $actualRequest = ApiRestRequestBuilder::buildRequest(self::TEST_USERNAME, self::TEST_PASSWORD);

        $expectedRequest = $this->buildExpectedRequest(null);

        $this->assertEquals($expectedRequest, $actualRequest);
    }

    public function testBuildEmptyStringRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest(self::TEST_USERNAME, self::TEST_PASSWORD, "");

        $expectedRequest = $this->buildExpectedRequest('');

        $this->assertEquals($expectedRequest, $actualRequest);
    }

    public function testBuildStringRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest(self::TEST_USERNAME, self::TEST_PASSWORD,"TEST");

        $expectedRequest = $this->buildExpectedRequest("TEST");

        $this->assertEquals($expectedRequest, $actualRequest);
    }

    public function testBuildObjectRequest()
    {
        $actualRequest = ApiRestRequestBuilder::buildRequest(self::TEST_USERNAME, self::TEST_PASSWORD, $this->buildProduct("productName", "universeId"));

        $expectedRequest = $this->buildExpectedRequest(
            ["name" => "productName", "universe" => ["id" => "universeId"]]
        );

        $this->assertEquals($expectedRequest, $actualRequest);
    }

    public function testBuildArrayObjectRequest()
    {
        $products = [
            $this->buildProduct("productName1", "universeId1"),
            $this->buildProduct("productName2", "universeId2"),
            $this->buildProduct("productName3", "universeId3")
        ];

        $actualRequest = ApiRestRequestBuilder::buildRequest(self::TEST_USERNAME, self::TEST_PASSWORD, $products);

        $expectedRequest = $this->buildExpectedRequest([
            ["name" => "productName1", "universe" => ["id" => "universeId1"]],
            ["name" => "productName2", "universe" => ["id" => "universeId2"]],
            ["name" => "productName3", "universe" => ["id" => "universeId3"]]
        ]);

        $this->assertEquals($expectedRequest, $actualRequest);
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

    protected function  buildExpectedRequest($body, $headers = [], $filters = [])
    {
        $expectedRequest = [
            'auth' => [
                self::TEST_USERNAME,
                self::TEST_PASSWORD,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            "body" => json_encode($body)
        ];

        $expectedRequest["headers"] = array_merge($expectedRequest["headers"], $headers);
        if(!empty($filters)){
            $expectedRequest["query"]= $filters;
        }

        return $expectedRequest;
    }
}