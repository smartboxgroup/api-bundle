<?php

namespace Smartbox\ApiBundle\Tests\Services\Soap;

use Smartbox\ApiBundle\Controller\SoapController;
use Smartbox\ApiBundle\Services\Soap\ServerBuilder;

class ServerBuilderTest extends \PHPUnit\Framework\TestCase
{
    private $serverBuilder;

    public function setUp()
    {
        $this->serverBuilder = new ServerBuilder();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetNotExistingServerClass()
    {
        $this->serverBuilder->setServerClass('\Set\Non\Existing\Class');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWhenWdslIsNotDefined()
    {
        $this->serverBuilder->build();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWhenHandlerIsNotConfigured()
    {
        $this->serverBuilder->withWsdl('wsdl_file.xml');

        $this->serverBuilder->build();
    }

    public function handlerProvider()
    {
        return [
            'Test using a handler class name' => [
                'handler' => SoapController::class,
            ],
            'Test using a handler object' => [
                'handler' => new SoapController(),
            ],
        ];
    }

    /**
     * @dataProvider handlerProvider
     *
     * @param mixed $handler
     */
    public function testBuildSoapServerUsingHandler($handler)
    {
        $this->serverBuilder->setServerClass(\SoapServer::class);
        $this->serverBuilder->withWsdl('Tests/Fixtures/Wsdl/wsdl_file.xml');
        $this->serverBuilder->withHandler($handler);

        $this->assertInstanceOf(\SoapServer::class, $this->serverBuilder->build());
    }
}
