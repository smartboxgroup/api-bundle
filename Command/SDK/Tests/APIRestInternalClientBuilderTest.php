<?php

namespace BifrostClient\Tests;

use Smartbox\BifrostSDK\BifrostClientBuilder;
use Smartbox\BifrostSDK\BifrostSDK;

class APIRestInternalClientBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Exception
     */
    public function testInvalidEnvironment()
    {
        \APIRestInternalClientBuilder::createClient('DUMMY_ENV', 'TEST', 'TEST');
    }

    public function testCreateBuilder()
    {
        $client = \APIRestInternalClientBuilder::createClient(\APIRestInternalClientBuilder::ENV_DEMO, 'TEST', 'TEST');

        $this->assertNotNull($client);
        $this->assertEquals(BifrostSDK::class, get_class($client));
    }
}
