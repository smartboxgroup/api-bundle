<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Metadata;

use Smartbox\ApiBundle\Metadata\JsonSchemaViewsRegistryBuilder;

/**
 * Class JsonSchemaViewsRegistryBuilderTest
 */
class JsonSchemaViewsRegistryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var JsonSchemaViewsRegistryBuilder */
    protected $builder;

    public function setUp()
    {
        $path = realpath(__DIR__.'/../Fixtures/schemas');
        $this->builder = new JsonSchemaViewsRegistryBuilder($path);
    }

    public function testItShouldBuildARegistryDefinition()
    {
        $definition = $this->builder->buildDefinition();

        $expectedMethodCalls = [
            ['addGroup', ['person-credit', 'http://example.com/schemas/person.schema.json', ['id', 'credit']]],
            ['addGroup', ['person-full', 'http://example.com/schemas/person.schema.json', ["id", "name", "surname", "birthDate", "password", "credit"]]],
            ['addGroup', ['person-public', 'http://example.com/schemas/person.schema.json', ['id', 'name', 'surname']]],
            ['addGroup', ['person-relationship', 'http://example.com/schemas/person-relationship.schema.json', ["parentPerson","childPerson", "type"]]],
            ['addGroup', ['person-relationship', 'http://example.com/schemas/person.schema.json', ["id"]]],
        ];

        $this->assertEquals($expectedMethodCalls, $definition->getMethodCalls());
    }
}
