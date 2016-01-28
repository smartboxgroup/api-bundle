<?php

namespace Smartbox\ApiBundle\Entity;

use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Serializer\Cache\SerializerCacheableInterface;
use Smartbox\CoreBundle\Type\Entity;
use Smartbox\CoreBundle\Type\EntityInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ApiEntity extends Entity implements SerializerCacheableInterface
{
    public function __construct()
    {
        $this->entityGroup = EntityInterface::GROUP_PUBLIC;
    }
}
