<?php

namespace Smartbox\ApiRestClient\Tests;

use GuzzleHttp\Psr7\Response;
use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestInternalClientBuilder;
use Smartbox\ApiRestClient\Environments;
use Smartbox\Integration\PlatformBundle\CanonicalDataModel\Universe\Universe;

class ApiRestInternalClientTest extends \PHPUnit_Framework_TestCase
{
    const TEST_USERNAME = 'admin';
    const TEST_PASSWORD = 'admin';

    public function getClient()
    {
        $client = ApiRestInternalClientBuilder::createClient(Environments::ENV_TEST, self::TEST_USERNAME, self::TEST_PASSWORD);
        return $client;
    }

    public function testSuccessfulRequest()
    {
        $client = $this->getClient();

        $response = $client->request(ApiRestInternalClient::HTTP_METHOD_GET, '/api/rest/poc/v0/timestamp');
        $this->assertEquals(Response::class, get_class($response));

        $this->assertEquals('200', $response->getStatusCode());

        $contents = $response->getBody()->getContents();
        $this->assertNotNull($contents);
    }

    /**
     * @expectedException \Exception
     */
    public function testBadHttpMethod()
    {
        $client = $this->getClient();

        $response = $client->request('DUMMY_METHOD', '/api/rest/poc/v0/timestamp');
    }

    /**
     * @expectedException \Exception
     */
    public function testBadCredentials()
    {
        $client = ApiRestInternalClientBuilder::createClient(Environments::ENV_TEST, 'test', 'test');
        $response = $client->request(ApiRestInternalClient::HTTP_METHOD_GET, '/api/rest/poc/v0/timestamp');
    }

}
