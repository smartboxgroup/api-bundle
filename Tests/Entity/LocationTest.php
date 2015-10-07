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
     * @test
     * @expectedException \Exception
     * @expectedExceptionMessage Parameter id returned by getIdParameters by class Something is not readable
     */
    public function it_should_not_accept_entity_with_invalid_id_parameters()
    {
        /** @var \Smartbox\ApiBundle\Entity\LocatableEntity|\PHPUnit_Framework_MockObject_MockObject $entity */
        $entity = $this->getMockBuilder('\Smartbox\ApiBundle\Entity\LocatableEntity')
            ->getMock()
        ;

        $entity->method('getIdParameters')
            ->will($this->returnValue(['id']))
        ;

        $entity->method('getType')
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

        $this->location = new Location($entity, $propertyAccessor);
    }

    /**
     * @test
     */
    public function it_should_read_data_from_an_entity()
    {
        $parameters = $this->location->getParametersAsArray();
        $this->assertTrue($parameters['id'] === 17);
    }

    /**
     * @test
     */
    public function it_should_get_api_method()
    {
        $this->assertEquals('getSomething', $this->location->getApiMethod());
    }

    /**
     * @test
     */
    public function it_should_set_and_get_url()
    {
        $url = 'http://example.com/something';
        $this->location->setUrl($url);
        $this->assertEquals($url, $this->location->getUrl());
    }

    /**
     * @test
     */
    public function it_should_set_and_get_api_service()
    {
        $apiService = 'someService';
        $this->location->setApiService($apiService);
        $this->assertEquals($apiService, $this->location->getApiService());
    }

    /**
     * @test
     */
    public function it_should_not_be_resolved_without_all_the_parameters_set()
    {
        $this->assertFalse($this->location->isResolved());
    }

    /**
     * @test
     */
    public function it_should_be_resolved_with_all_the_parameters_set()
    {
        $this->location->setUrl('someUrl');
        $this->location->setApiService('someService');
        $this->assertTrue($this->location->isResolved());
    }

    /**
     * @test
     */
    public function it_should_set_and_get_parameters()
    {
        $parameters = [
            new KeyValue('foo', 'bar'),
            new KeyValue('baz', null)
        ];

        $this->location->setParameters($parameters);
        $this->assertEquals($parameters, $this->location->getParameters());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_should_not_accept_invalid_parameters()
    {
        $parameters = [
            'foo' => 'bar',
        ];

        $this->location->setParameters($parameters);
        $this->assertEquals($parameters, $this->location->getParameters());
    }

    /**
     * @test
     */
    public function it_should_get_parameters_as_array()
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

    /**
     * @test
     */
    public function it_should_get_a_rest_header_value_based_on_the_url()
    {
        $this->location->setUrl('foo');
        $this->assertEquals('foo', $this->location->getRESTHeaderValue());
    }
}
