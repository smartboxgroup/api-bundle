<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Entity;

use JMS\Serializer\Annotation as JMS;
use Smartbox\ApiBundle\Entity\ApiEntity;
use Smartbox\ApiBundle\Entity\LocatableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Item.
 */
class Item extends ApiEntity implements LocatableEntity
{
    /**
     * Numeric id of the item.
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank(groups={"list", "public"})
     * @JMS\Type("integer")
     * @JMS\Expose
     * @JMS\Groups({"list", "public"})
     *
     * @var int
     */
    protected $id;

    /**
     * Name of an item.
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"update","list", "public"})
     *
     * @var string
     */
    protected $name;

    /**
     * Description of the contents of the box.
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"update","list", "public"})
     *
     * @var string
     */
    protected $description;

    /**
     * Type of item.
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @JMS\Until("v1")
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"update", "public"})
     *
     * @var string
     */
    protected $type;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the name of the API method that gets this entity.
     *
     * @return string
     */
    public function getApiGetterMethod()
    {
        return 'getItem';
    }

    /**
     * Returns an array with the parameters that identify this entity.
     *
     * @return array
     */
    public function getIdParameters()
    {
        return ['id'];
    }
}
