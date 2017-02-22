<?php
namespace Smartbox\ApiRestClient\Tests;

use Smartbox\ApiRestClient\Clients\EaiV0Client;
use Smartbox\ApiRestClient\ApiRestInternalClientBuilder;
use Smartbox\ApiRestClient\Environments;
use Smartbox\Integration\PlatformBundle\CanonicalDataModel\Universe\Universe;

/**
 * Class EaiV0ClientTest
 *
 * @package Smartbox\ApiRestClient\Tests
 */
class EaiV0ClientTest extends \PHPUnit_Framework_TestCase
{
    const TEST_USERNAME = 'admin';
    const TEST_PASSWORD = 'admin';

    public function testBroadcastUniverse()
    {
        /** @var EaiV0Client $client */
        $client = ApiRestInternalClientBuilder::createClient(EaiV0Client::$class, Environments::ENV_INT_APOLLO, self::TEST_USERNAME, self::TEST_PASSWORD, false);

        $entity = new Universe();
        $entity->setId("AVD");
        $response = $client->broadcastUniverse($entity, array("testHeader" => "value"));

        $this->assertEquals("202", $response->getStatusCode());
        $this->assertEmpty($response->getBody());
        $headers = $response->getHeaders();
        $this->assertArrayHasKey("x-transaction-id", $headers);
    }

    public function testGetVoucherDetails()
    {
        /** @var EaiV0Client $client */
        $client = ApiRestInternalClientBuilder::createClient(EaiV0Client::$class, Environments::ENV_INT_APOLLO, self::TEST_USERNAME, self::TEST_PASSWORD, false);

        $response = $client->getVoucherDetails("648818503", "false");

        $this->assertEquals("200", $response->getStatusCode());
        $headers = $response->getHeaders();
        $this->assertArrayHasKey("x-transaction-id", $headers);

        $this->assertGreaterThan(0, count($response->getBody()));

        $this->assertInstanceOf('\Smartbox\Integration\PlatformBundle\CanonicalDataModel\Voucher\VoucherDetail' , $response->getBody());
    }
}