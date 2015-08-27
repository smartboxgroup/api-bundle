<?php

namespace Smartbox\ApiBundle\Entity;


use BeSimple\SoapCommon\Type\KeyValue\String;
use Smartbox\CoreBundle\Entity\EntityInterface;
use Smartbox\CoreBundle\Entity\Traits\HasGroup;
use Smartbox\CoreBundle\Entity\Traits\HasVersion;

class KeyValue extends String implements EntityInterface
{

    use HasGroup;
    use HasVersion;

    public function __construct($key = null, $value = null)
    {
        parent::__construct($key, $value);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return get_class($this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }
}