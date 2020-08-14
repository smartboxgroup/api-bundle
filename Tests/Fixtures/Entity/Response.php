<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Entity;

use JMS\Serializer\Annotation as JMS;
use Smartbox\ApiBundle\Entity\ApiEntity;
use Symfony\Component\Validator\Constraints as Assert;

class Response extends ApiEntity
{
    /**
     * Transaction id.
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @Assert\Length(min="1")
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"list", "public", "logs"})
     * @JMS\SerializedName("transactionId")
     *
     * @var string
     */
    protected $transactionId;

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }
}
