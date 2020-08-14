<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Entity;

use JMS\Serializer\Annotation as JMS;
use Smartbox\ApiBundle\Entity\ApiEntity;
use Symfony\Component\Validator\Constraints as Assert;

class BoxBrief extends ApiEntity
{
    /**
     * The related product.
     *
     * @Assert\Type(type="Smartbox\ApiBundle\Tests\Fixtures\Entity\BoxWrong")
     * @Assert\Valid
     * @JMS\Type("Smartbox\ApiBundle\Tests\Fixtures\Entity\BoxWrong")
     * @JMS\Expose
     * @JMS\Groups({"logs", "public"})
     * @JMS\SerializedName("box")
     *
     * @var Box
     */
    protected $box;

    /**
     * @return Product
     */
    public function getBox()
    {
        return $this->box;
    }

    /**
     * @param Product $box
     */
    public function setBox($box)
    {
        $this->box = $box;
    }
}
