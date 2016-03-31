<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Metadata;

use Doctrine\Common\Annotations\AnnotationReader;
use Smartbox\ApiBundle\Metadata\JsonSchemaGroupsDriver;
use Smartbox\ApiBundle\Tests\BaseKernelTestCase;
use Smartbox\ApiBundle\Tests\Fixtures\Entity\Person;
use Smartbox\ApiBundle\Tests\Fixtures\Entity\PersonWithInvalidSchemaFile;
use Smartbox\ApiBundle\Tests\Fixtures\Entity\PersonWithMissingSchemaFile;

/**
 * Class JsonSchemaGroupsDriverTest
 */
class JsonSchemaGroupsDriverTest extends BaseKernelTestCase
{
    /** @var JsonSchemaGroupsDriver */
    protected $driver;

    public function setUp()
    {
        parent::setUp();
        /** @var AnnotationReader $reader */
        $reader = $this->getContainer()->get('annotation_reader');
        $jsonSchemaFolder = realpath(__DIR__.'/../Fixtures/schemas/');
        $this->driver = new JsonSchemaGroupsDriver($reader, $jsonSchemaFolder);
    }

    public function testItShouldReadGroupsFromClass()
    {
        $reflectionClass = new \ReflectionClass(Person::class);
        /** @var ClassMetadata $metadata */
        $metadata = $this->driver->loadMetadataForClass($reflectionClass);

        $this->assertEquals(['public', 'credit', 'full'], $metadata->propertyMetadata['id']->groups);
        $this->assertEquals(['public', 'full'], $metadata->propertyMetadata['name']->groups);
        $this->assertEquals(['public', 'full'], $metadata->propertyMetadata['surname']->groups);
        $this->assertEquals(['credit', 'full'], $metadata->propertyMetadata['credit']->groups);
        $this->assertEquals(['full'], $metadata->propertyMetadata['birthDate']->groups);
        $this->assertEquals(['full'], $metadata->propertyMetadata['password']->groups);
    }

    public function testItShouldFailWithMissingFile()
    {
        $this->setExpectedExceptionRegExp(\InvalidArgumentException::class, '/is missing in path/');
        $reflectionClass = new \ReflectionClass(PersonWithMissingSchemaFile::class);
        $this->driver->loadMetadataForClass($reflectionClass);
    }

    public function testItShouldFailWithInvalidSchemaFile()
    {
        $this->setExpectedExceptionRegExp(\InvalidArgumentException::class, '/does not contain a valid json schema/');
        $reflectionClass = new \ReflectionClass(PersonWithInvalidSchemaFile::class);
        $this->driver->loadMetadataForClass($reflectionClass);
    }
}
