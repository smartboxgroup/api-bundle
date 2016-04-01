<?php

namespace Smartbox\ApiBundle\Metadata\Driver;

use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\Metadata\ClassMetadata;
use Metadata\Driver\DriverInterface;
use Metadata\PropertyMetadata;
use Smartbox\ApiBundle\Annotation\JsonSchema;
use Smartbox\ApiBundle\Metadata\JsonSchemaViewsRegistry;

/**
 * Class JsonSchemaGroupsDriver
 */
class JsonSchemaGroupsDriver implements DriverInterface
{
    /** @var Reader  */
    protected $reader;

    /** @var JsonSchemaViewsRegistry */
    protected $registry;

    /**
     * JsonSchemaGroupsDriver constructor.
     *
     * @param Reader                  $reader
     * @param JsonSchemaViewsRegistry $registry
     */
    public function __construct(Reader $reader, JsonSchemaViewsRegistry $registry)
    {
        $this->reader = $reader;
        $this->registry = $registry;
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $annotations = $this->reader->getClassAnnotations($class);
        $groupsByProperty = [];

        foreach($annotations as $annotation) {
            if (!$annotation instanceof JsonSchema) {
                continue;
            }

            $schema = $annotation->id;
            $groups = $this->registry->getGroups($schema);

            if (isset($schema['views'])) {
                foreach($schema['views'] as $group => $fields) {
                    $fields = (array) $fields;
                    foreach ($fields as $field) {
                        if (!isset($groupsByProperty[$field])) {
                            $groupsByProperty[$field] = [];
                        }

                        $groupsByProperty[$field][] = $group;
                    }
                }
            }

            foreach ($groups as $group => $fields) {
                $fields = (array) $fields;
                foreach ($fields as $field) {
                    if (!isset($groupsByProperty[$field])) {
                        $groupsByProperty[$field] = [];
                    }

                    $groupsByProperty[$field][] = $group;
                }
            }
        }

        if (empty($groupsByProperty)) {
            return null;
        }

        $classMetadata = new ClassMetadata($class->name);

        foreach ($groupsByProperty as $propertyName => $groups) {
            $propertyMetadata = new PropertyMetadata($class->getName(), $propertyName);
            $propertyMetadata->groups = array_unique($groups);
            $classMetadata->addPropertyMetadata($propertyMetadata);
        }

        return $classMetadata;
    }
}
