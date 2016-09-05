<?php

namespace Smartbox\ApiBundle\Tests\SDK;


use Smartbox\ApiBundle\Tests\SDK\Fixture\Entity\Product;
use Smartbox\ApiBundle\Tests\SDK\Fixture\MockApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestInternalClientBuilder;

class ApiRestInternalClientBuilderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENV           = "test";
    const TEST_USERNAME      = "test";
    const TEST_PASSWORD      = "test";

    /**
     * @expectedException \Exception
     */
    public function testInvalidEnvironment()
    {
        ApiRestInternalClientBuilder::createClient('DUMMY_ENV', self::TEST_USERNAME, self::TEST_PASSWORD);
    }

    public function testDefaultClient()
    {
        $client = ApiRestInternalClientBuilder::createClient(self::TEST_ENV, self::TEST_USERNAME, self::TEST_PASSWORD);

        $this->assertNotNull($client);
        $this->assertEquals(ApiRestInternalClient::class, get_class($client));
    }

    /**
     * @expectedException \LogicException
     */
    public function testUnknownClientClass()
    {
        ApiRestInternalClientBuilder::createClient(self::TEST_ENV, self::TEST_USERNAME, self::TEST_PASSWORD, "Dummy_Client");
    }

    public function testSpecificClient()
    {
        $client = ApiRestInternalClientBuilder::createClient(self::TEST_ENV, self::TEST_USERNAME, self::TEST_PASSWORD, MockApiRestInternalClient::class);
        $this->assertEquals(MockApiRestInternalClient::class, get_class($client));
    }

    /**
     * @expectedException \LogicException
     */
    public function testInvalidClient()
    {
        ApiRestInternalClientBuilder::createClient(self::TEST_ENV, self::TEST_USERNAME, self::TEST_PASSWORD, Product::class);
    }
}
