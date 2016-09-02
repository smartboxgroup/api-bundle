<?php


namespace Smartbox\ApiBundle\Tests\SDK;


use GuzzleHttp\Psr7\Response;
use Smartbox\ApiRestClient\ApiRestResponseBuilder;

class ApiRestResponseBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyResponse()
    {
        $guzzleResponse = new Response("200");
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, null);
        $this->assertNotNull($response);
    }

    public function testCorrectResponse()
    {
        $guzzleResponse = new Response("200");
        $response = ApiRestResponseBuilder::buildResponse($guzzleResponse, null);
        $this->assertNotNull($response);
    }
}