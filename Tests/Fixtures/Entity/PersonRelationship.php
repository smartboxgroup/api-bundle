<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Entity;

/**
 * Class PersonRelationship
 *
 * @Smartbox\ApiBundle\Annotation\JsonSchema("http://example.com/schemas/person-relationship.schema.json")
 */
class PersonRelationship
{
    /**
     * @var Person
     */
    protected $parentPerson;

    /**
     * @var Person
     */
    protected $childPerson;

    /**
     * @var string
     */
    protected $type;

    /**
     * @return Person
     */
    public function getParentPerson()
    {
        return $this->parentPerson;
    }

    /**
     * @param Person $parentPerson
     *
     * @return PersonRelationship
     */
    public function setParentPerson($parentPerson)
    {
        $this->parentPerson = $parentPerson;
        return $this;
    }

    /**
     * @return Person
     */
    public function getChildPerson()
    {
        return $this->childPerson;
    }

    /**
     * @param Person $childPerson
     *
     * @return PersonRelationship
     */
    public function setChildPerson($childPerson)
    {
        $this->childPerson = $childPerson;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return PersonRelationship
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
