<?php

namespace Smartbox\ApiBundle\Entity;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class BasicResponse extends ApiEntity
{

    /**
     * Code describing the result of the operation
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank
     * @JMS\Groups({"public"})
     * @JMS\Type("integer")
     */
    protected $code;

    /**
     * Message describing the result of the operation
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @JMS\Groups({"public"})
     * @JMS\Type("string")
     */
    protected $message;

    function __construct($code = null, $message = null)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        if (!empty($code) && !is_numeric($code)) {
            throw new \InvalidArgumentException("Expected null or numeric value in method setCode");
        }

        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        if (!empty($message) && !is_string($message)) {
            throw new \InvalidArgumentException("Expected null or string in method setMessage");
        }

        $this->message = $message;
    }
}