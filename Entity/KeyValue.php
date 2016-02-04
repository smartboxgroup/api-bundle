<?php

namespace Smartbox\ApiBundle\Entity;


use JMS\Serializer\Annotation as JMS;
use Smartbox\CoreBundle\Type\EntityInterface;
use Smartbox\CoreBundle\Type\Traits\HasInternalType;

class KeyValue extends ApiEntity implements EntityInterface
{
    /**
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"public"})
     * @var string
     */
    protected $key;

    /**
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"public"})
     * @var string
     */
    protected $value;

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    public function __construct($key = null, $value = null)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }
}