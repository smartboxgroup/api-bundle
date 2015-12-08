<?php

namespace Smartbox\ApiBundle\Test\Entity;

use Smartbox\ApiBundle\Entity\Location;
use Smartbox\ApiBundle\EventListener\ThrottlingListener;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ThrottlingListenerTest extends KernelTestCase
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ThrottlingListener
     */
    private $throttlingListener;

    public function setUp()
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $this->application = new Application($kernel);
        $this->container = $kernel->getContainer();

        $eventDispatcher = $this->container->get('event_dispatcher');
        $rateLimitService = $this->container->get('noxlogic_rate_limit.rate_limit_service');
        $pathLimitProcessor = $this->container->get('noxlogic_rate_limit.path_limit_processor');

        $this->throttlingListener = new ThrottlingListener($eventDispatcher, $rateLimitService, $pathLimitProcessor);
    }

    public static function getKernelClass()
    {
        return \AppKernel::class;
    }


//    /**
//     * @expectedException \Exception
//     * @expectedExceptionMessage Parameter id returned by getIdParameters by class Something is not readable
//     */
//    public function testItShouldNotAcceptEntityWithInvalidIdParameters()
//    {
//        /** @var \Smartbox\ApiBundle\Entity\LocatableEntity|\PHPUnit_Framework_MockObject_MockObject $entity */
//        $entity = $this->getMockBuilder('\Smartbox\ApiBundle\Entity\LocatableEntity')
//            ->getMock()
//        ;
//
//        $entity->method('getIdParameters')
//            ->will($this->returnValue(['id']))
//        ;
//
//        $entity->method('getType')
//            ->will($this->returnValue('Something'))
//        ;
//
//        /** @var \Symfony\Component\PropertyAccess\PropertyAccessor|\PHPUnit_Framework_MockObject_MockObject $propertyAccessor */
//        $propertyAccessor = $this->getMockBuilder('\Symfony\Component\PropertyAccess\PropertyAccessor')
//            ->getMock()
//        ;
//
//        $propertyAccessor->method('isReadable')
//            ->with($entity, 'id')
//            ->will($this->returnValue(false))
//        ;
//
//        $this->location = new Location($entity, $propertyAccessor);
//    }
//
//    public function testItShouldReadDataFromAnEntity()
//    {
//        $parameters = $this->location->getParametersAsArray();
//        $this->assertTrue($parameters['id'] === 17);
//    }
//
//    public function testItShouldGetApiMethod()
//    {
//        $this->assertEquals('getSomething', $this->location->getApiMethod());
//    }
//
//    public function testItShouldSetAndGetUrl()
//    {
//        $url = 'http://example.com/something';
//        $this->location->setUrl($url);
//        $this->assertEquals($url, $this->location->getUrl());
//    }
//
//    public function testItShouldSetAndGetApiService()
//    {
//        $apiService = 'someService';
//        $this->location->setApiService($apiService);
//        $this->assertEquals($apiService, $this->location->getApiService());
//    }
//
//    public function testItShouldNotBeResolvedWithoutAllTheParametersSet()
//    {
//        $this->assertFalse($this->location->isResolved());
//    }
//
//    public function testItShouldBeResolvedWithAllTheParametersSet()
//    {
//        $this->location->setUrl('someUrl');
//        $this->location->setApiService('someService');
//        $this->assertTrue($this->location->isResolved());
//    }
//
//    public function testItShouldSetAndGetParameters()
//    {
//        $parameters = [
//            new KeyValue('foo', 'bar'),
//            new KeyValue('baz', null)
//        ];
//
//        $this->location->setParameters($parameters);
//        $this->assertEquals($parameters, $this->location->getParameters());
//    }
//
//    /**
//     * @expectedException \InvalidArgumentException
//     */
//    public function testItShouldNotAcceptInvalidParameters()
//    {
//        $parameters = [
//            'foo' => 'bar',
//        ];
//
//        $this->location->setParameters($parameters);
//        $this->assertEquals($parameters, $this->location->getParameters());
//    }
//
//    public function testItShouldGetParametersAsArray()
//    {
//        $parameters = [
//            new KeyValue('foo', 'bar'),
//            new KeyValue('baz', null)
//        ];
//
//        $expected = [
//            'foo' => 'bar',
//            'baz' => null
//        ];
//
//        $this->location->setParameters($parameters);
//        $this->assertEquals($expected, $this->location->getParametersAsArray());
//    }
//
//    public function testItShouldGetARestHeaderValueBasedOnTheUrl()
//    {
//        $this->location->setUrl('foo');
//        $this->assertEquals('foo', $this->location->getRESTHeaderValue());
//    }
}
