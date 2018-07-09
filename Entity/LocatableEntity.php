<?php

namespace Smartbox\ApiBundle\Entity;

use Smartbox\CoreBundle\Type\EntityInterface;

interface LocatableEntity extends EntityInterface
{
    /**
     * Returns the name of the API method that gets this entity.
     *
     * @return string
     */
    public function getApiGetterMethod();

    /**
     * Returns an array with the parameters that identify this entity.
     *
     * @return array
     */
    public function getIdParameters();
}
