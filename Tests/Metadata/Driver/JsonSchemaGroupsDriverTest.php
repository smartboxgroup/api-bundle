<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Metadata\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\Metadata\ClassMetadata;
use Smartbox\ApiBundle\Metadata\Driver\JsonSchemaGroupsDriver;
use Smartbox\ApiBundle\Metadata\JsonSchemaViewsRegistry;
use Smartbox\ApiBundle\Tests\BaseKernelTestCase;
use Smartbox\ApiBundle\Tests\Fixtures\Entity\Person;
use Smartbox\ApiBundle\Tests\Fixtures\Entity\PersonRelationship;

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
        /** @var JsonSchemaViewsRegistry $registry */
        $registry = $this->getContainer()->get('smartapi.json_schema_views.registry');
        $this->driver = new JsonSchemaGroupsDriver($reader, $registry);
    }

    /**
     * @dataProvider groupsDataProvider
     *
     * @param       $class
     * @param array $expectedGroupsByField
     */
    public function testItShouldReadGroupsFromClass($class, array $expectedGroupsByField)
    {
        $reflectionClass = new \ReflectionClass($class);
        /** @var ClassMetadata $metadata */
        $metadata = $this->driver->loadMetadataForClass($reflectionClass);

        foreach ($expectedGroupsByField as $field => $groups) {
            $this->assertEquals($groups, $metadata->propertyMetadata[$field]->groups);
        }
    }

    public function groupsDataProvider()
    {
        return [
            [
                Person::class,
                [
                    'id' => ['person-credit', 'person-full', 'person-public', 'person-relationship'],
                    'credit' => ['person-credit', 'person-full'],
                    'name' => ['person-full', 'person-public'],
                    'surname' => ['person-full', 'person-public'],
                    'birthDate' => ['person-full'],
                    'password' => ['person-full'],
                ]
            ],
            [
                PersonRelationship::class,
                [
                    'parentPerson' => ['person-relationship'],
                    'childPerson' => ['person-relationship'],
                    'type' => ['person-relationship']
                ]
            ],
        ];
    }
}

