<?php

namespace Smartbox\ApiBundle\Tests\Services\Serializer\Exclusion;

use JMS\Serializer\Metadata\PropertyMetadata;
use PHPUnit\Framework\TestCase;
use Smartbox\ApiBundle\Services\Serializer\Exclusion\PreserveArrayTypeStrategy;

class PreserveArrayTypeStrategyTest extends TestCase
{
    /** @var PreserveArrayTypeStrategy */
    private $strategy;

    /** @var string */
    private $class;

    /** @var string used to test some metadata */
    public $sampleProperty = 'sampleValue';

    public function setUp()
    {
        $this->strategy = new PreserveArrayTypeStrategy();
        $this->class = get_class($this); // any dummy class to have a context
    }

    public function testItNeverSkipsClasses()
    {
        /** @var \JMS\Serializer\Metadata\ClassMetadata $metadata */
        $metadata = $this->getMockBuilder('\JMS\Serializer\Metadata\ClassMetadata')
            ->setConstructorArgs([$this->class])
            ->getMock();

        /** @var \JMS\Serializer\Context $context */
        $context = $this->getMockBuilder('\JMS\Serializer\Context')->getMock();

        $this->assertFalse($this->strategy->shouldSkipClass($metadata, $context));
    }

    public function testItPreservesArrayFields()
    {
        $propertyMetadata = new PropertyMetadata($this->class, 'sampleProperty');
        $propertyMetadata->setType('array');

        /** @var \JMS\Serializer\Context $context */
        $context = $this->getMockBuilder('\JMS\Serializer\Context')->getMock();

        $this->assertTrue($this->strategy->shouldSkipProperty($propertyMetadata, $context));
    }
}
