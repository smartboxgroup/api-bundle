<?php

namespace Smartbox\ApiBundle\Tests\Services\Soap;

use Smartbox\ApiBundle\Services\Soap\ServerBuilder;

class ServerBuilderTest extends \PHPUnit_Framework_TestCase
{
    private $serverBuilder;

    public function setUp()
    {
        $this->serverBuilder = new ServerBuilder;
    }

    public function testSetNotExistingServerClass()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $this->serverBuilder->setServerClass('\Set\Non\Existing\Class');
    }

    public function testWhenWdslIsNotDefined()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $this->serverBuilder->build();
    }
}