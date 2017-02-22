<?php
namespace Smartbox\ApiRestClient\Tests\Fixture\Entity;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Universe
 *
 * @package Smartbox\ApiRestClient\Tests\Fixture\Entity
 */
class Universe
{
    /**
     * The unique identifier of the Universe
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @Assert\Length(min="1")
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\SerializedName("id")
     * @var string
     */
    protected $id;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}