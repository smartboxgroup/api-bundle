<?php

namespace Smartbox\ApiBundle\Entity;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Entity\Entity;
use Smartbox\CoreBundle\Entity\EntityInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ApiEntity extends Entity
{
    public function __construct()
    {
        $this->group = EntityInterface::GROUP_PUBLIC;
    }
}
