<?php

namespace Smartbox\ApiBundle\Metadata;

/**
 * Class JsonSchemaViewsRegistry
 */
class JsonSchemaViewsRegistry
{
    /** @var array */
    protected $registry;

    /**
     * JsonSchemaGroupsRegistry constructor.
     *
     * @param array $registry
     */
    public function __construct($registry = [])
    {
        $this->registry = $registry;
    }

    /**
     * Adds a group for a specific schema
     *
     * @param string $name
     * @param string $entitySchema
     * @param string|array $fields
     *
     * @return $this
     */
    public function addGroup($name, $entitySchema, $fields)
    {
        if (!isset($this->registry[$entitySchema])) {
            $this->registry[$entitySchema] = [];
        }

        $this->registry[$entitySchema][$name] = (array) $fields;

        return $this;
    }

    /**
     * Get groups for a specific schema
     * @param string $entitySchema
     *
     * @return array
     */
    public function getGroups($entitySchema)
    {
        if (!isset($this->registry[$entitySchema])) {
            return [];
        }

        return $this->registry[$entitySchema];
    }

    /**
     * Gets the registry as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->registry;
    }
}
