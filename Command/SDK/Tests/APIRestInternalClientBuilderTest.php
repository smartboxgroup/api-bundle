<?php

namespace Smartbox\ApiRestClient\Tests;

use Smartbox\ApiRestClient\ApiRestInternalClient;
use Smartbox\ApiRestClient\ApiRestInternalClientBuilder;

class ApiRestInternalClientBuilderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENV = "test";
    /**
     * @expectedException \Exception
     */
    public function testInvalidEnvironment()
    {
        ApiRestInternalClientBuilder::createClient('DUMMY_ENV', 'TEST', 'TEST');
    }

    public function testBuilderDefaultClient()
    {
        $client = ApiRestInternalClientBuilder::createClient(self::TEST_ENV, 'TEST', 'TEST');

        $this->assertNotNull($client);
        $this->assertEquals(ApiRestInternalClient::class, get_class($client));
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidClientClass()
    {
        ApiRestInternalClientBuilder::createClient(self::TEST_ENV, 'TEST', 'TEST', "Dummy_Client");
    }
}
