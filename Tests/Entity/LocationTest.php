<?php
/**
 * Created by PhpStorm.
 * User: Luciano.Mammino
 * Date: 18/09/2015
 * Time: 15:32
 */

namespace Smartbox\ApiBundle\Test\Entity;

use Smartbox\ApiBundle\Entity\KeyValue;
use Smartbox\ApiBundle\Entity\Location;

class LocationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Location
     */
    private $location;

    public function setup()
    {
        /** @var \Smartbox\ApiBundle\Entity\LocatableEntity|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->getMockBuilder('\Smartbox\ApiBundle\Entity\LocatableEntity')
            ->getMock()
        ;

        $entity->method('getIdParameters')
            ->will($this->returnValue(['id']))
        ;

        $entity->method('getApiGetterMethod')
            ->will($this->returnValue('getSomething'))
        ;

        /** @var \Symfony\Component\PropertyAccess\PropertyAccessor|\PHPUnit_Framework_MockObject_MockObject $propertyAccessor */
        $propertyAccessor = $this->getMockBuilder('\Symfony\Component\PropertyAccess\PropertyAccessor')
            ->getMock()
        ;

        $propertyAccessor->method('isReadable')
            ->with($entity, 'id')
            ->will($this->returnValue(true))
        ;

        $propertyAccessor->method('getValue')
            ->with($entity, 'id')
            ->will($this->returnValue(17))
        ;

        $this->location = new Location($entity, $propertyAccessor);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Parameter id returned by getIdParameters by class Something is not readable
     */
    public function testItShouldNotAcceptEntityWithInvalidIdParameters()
    {
        /** @var \Smartbox\ApiBundle\Entity\LocatableEntity|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->getMockBuilder('\Smartbox\ApiBundle\Entity\LocatableEntity')
            ->getMock()
        ;

        $entity->method('getIdParameters')
            ->will($this->returnValue(['id']))
        ;

        $entity->method('getInternalType')
            ->will($this->returnValue('Something'))
        ;

        /** @var \Symfony\Component\PropertyAccess\PropertyAccessor|\PHPUnit_Framework_MockObject_MockObject $propertyAccessor */
        $propertyAccessor = $this->getMockBuilder('\Symfony\Component\PropertyAccess\PropertyAccessor')
            ->getMock()
        ;

        $propertyAccessor->method('isReadable')
            ->with($entity, 'id')
            ->will($this->returnValue(false))
        ;

        new Location($entity, $propertyAccessor);
    }

    public function testItShouldReadDataFromAnEntity()
    {
        $parameters = $this->location->getParametersAsArray();
        $this->assertTrue($parameters['id'] === 17);
    }

    public function testItShouldGetApiMethod()
    {
        $this->assertEquals('getSomething', $this->location->getApiMethod());
    }

    public function testItShouldSetAndGetUrl()
    {
        $url = 'http://example.com/something';
        $this->location->setUrl($url);
        $this->assertEquals($url, $this->location->getUrl());
    }

    public function testItShouldSetAndGetApiService()
    {
        $apiService = 'someService';
        $this->location->setApiService($apiService);
        $this->assertEquals($apiService, $this->location->getApiService());
    }

    public function testItShouldNotBeResolvedWithoutAllTheParametersSet()
    {
        $this->assertFalse($this->location->isResolved());
    }

    public function testItShouldBeResolvedWithAllTheParametersSet()
    {
        $this->location->setUrl('someUrl');
        $this->location->setApiService('someService');
        $this->assertTrue($this->location->isResolved());
    }

    public function testItShouldSetAndGetParameters()
    {
        $parameters = [
            new KeyValue('foo', 'bar'),
            new KeyValue('baz', null)
        ];

        $this->location->setParameters($parameters);
        $this->assertEquals($parameters, $this->location->getParameters());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testItShouldNotAcceptInvalidParameters()
    {
        $parameters = [
            'foo' => 'bar',
        ];

        $this->location->setParameters($parameters);
        $this->assertEquals($parameters, $this->location->getParameters());
    }

    public function testItShouldGetParametersAsArray()
    {
        $parameters = [
            new KeyValue('foo', 'bar'),
            new KeyValue('baz', null)
        ];

        $expected = [
            'foo' => 'bar',
            'baz' => null
        ];

        $this->location->setParameters($parameters);
        $this->assertEquals($expected, $this->location->getParametersAsArray());
    }

    public function testItShouldGetARestHeaderValueBasedOnTheUrl()
    {
        $this->location->setUrl('foo');
        $this->assertEquals('foo', $this->location->getRESTHeaderValue());
    }
}
