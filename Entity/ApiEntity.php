<?php

namespace Smartbox\ApiBundle\Entity;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Entity\Entity;
use Symfony\Component\Validator\Constraints as Assert;

class ApiEntity extends Entity

{
    public function __construct()
    {
        $this->group = Entity::GROUP_PUBLIC;
    }


}