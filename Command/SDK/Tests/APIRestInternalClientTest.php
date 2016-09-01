<?php

namespace Smartbox\BifrostSDK\Tests;

use GuzzleHttp\Psr7\Response;
use Smartbox\BifrostSDK\BifrostClient;
use Smartbox\BifrostSDK\BifrostClientBuilder;
use Smartbox\BifrostSDK\ChecksV0SDK;
use Smartbox\Integration\PlatformBundle\CanonicalDataModel\Universe\Universe;

class APIRestInternalClientTest extends \PHPUnit_Framework_TestCase
{
    const TEST_USERNAME = 'admin';
    const TEST_PASSWORD = 'admin';

    public function testSuccessfulRequest()
    {
        $client = BifrostClientBuilder::createClient(BifrostClientBuilder::ENV_DEMO, self::TEST_USERNAME, self::TEST_PASSWORD);

        $response = $client->request(BifrostClient::HTTP_METHOD_GET, '/api/rest/poc/v0/timestamp');
        $this->assertEquals(Response::class, get_class($response));

        $this->assertEquals('200', $response->getStatusCode());
    }

    /**
     * @expectedException \Exception
     */
    public function testBadHttpMethod()
    {
        $client = BifrostClientBuilder::createClient(BifrostClientBuilder::ENV_DEMO, self::TEST_USERNAME, self::TEST_PASSWORD);

        $response = $client->request('DUMMY_METHOD', '/api/rest/poc/v0/timestamp');
    }

    /**
     * @expectedException \Exception
     */
    public function testBadCredentials()
    {
        $client = BifrostClientBuilder::createClient(BifrostClientBuilder::ENV_DEMO, 'test', 'test');
        $response = $client->request(BifrostClient::HTTP_METHOD_GET, '/api/rest/poc/v0/timestamp');
    }

    public function testSuccessfulRequestWithSerialization()
    {
        $client = BifrostClientBuilder::createClient(BifrostClientBuilder::ENV_DEMO, self::TEST_USERNAME, self::TEST_PASSWORD);
        $universe = new Universe();
        $universe->setId('10');
        $response = $client->request(BifrostClient::HTTP_METHOD_POST, '/api/rest/eai/v0/broadcast/universe', $universe);

        $this->assertNotNull($response);
        $this->assertEquals('202', $response->getStatusCode());
    }

    public function testEncapsulatedRequest()
    {
        $client = BifrostClientBuilder::createClient(BifrostClientBuilder::ENV_DEMO, self::TEST_USERNAME, self::TEST_PASSWORD);

        $universe = new Universe();
        $universe->setId('10');

        $response = $client->broadcastUniverse($universe);

        $this->assertNotNull($response);
        $this->assertEquals('202', $response->getStatusCode());
    }

    /**
     * @expectedException \TypeError
     */
    public function testEncapsulatedRequestWithWrongType()
    {
        $client = BifrostClientBuilder::createClient(BifrostClientBuilder::ENV_DEMO, self::TEST_USERNAME, self::TEST_PASSWORD);

        $universe = new Universe();
        $universe->setId('10');

        $response = $client->broadcastBrandInformation($universe);
    }


    /**
     * @return array
     */
    public function testArray ()
    {
        $client = BifrostClientBuilder::createClient(BifrostClientBuilder::ENV_DEMO, self::TEST_USERNAME, self::TEST_PASSWORD);

        $universe = new Universe();
        $universe->setId('10');

        $response = $client->broadcastBrandInformation($universe);
    }

    public function testChecks()
    {
        $client = BifrostClientBuilder::createClient(BifrostClientBuilder::ENV_DEMO, self::TEST_USERNAME, self::TEST_PASSWORD, ChecksV0SDK::class);
    }
}
