<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Entity;

use Smartbox\ApiBundle\Entity\LocatableEntity;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;
use Smartbox\ApiBundle\Entity\ApiEntity;

/**
 * Class Box
 * @package Smartbox\ApiBundle\Tests\Fixtures\Entity
 * @Soap\Alias("Box")
 */
class Box extends ApiEntity implements LocatableEntity
{

    const STATUS_TRANSIT = 'transit';
    const STATUS_STORED = 'stored';

    /**
     * Numeric id of the box
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank(groups={"list", "public"})
     * @JMS\Type("integer")
     * @JMS\Expose
     * @JMS\Groups({"list", "public"})
     * @var int
     */
    protected $id;

    /**
     * Description of the contents of the box
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @Assert\Length(min=10, max=200)
     * @JMS\Since("v2")
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"update","list", "public"})
     * @var string
     */
    protected $description;

    /**
     * Status of the box regarding whether it is in transit or awaits stored in a warehouse
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank(groups={"list", "public"})
     * @Assert\Choice(choices = {"transit", "stored"})
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"update","list", "public"})
     * @var string
     */
    protected $status;

    /**
     * Length of the box in cm
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank
     * @Assert\Range( min = 0, max = 100)
     * @JMS\Type("integer")
     * @JMS\Expose
     * @JMS\Groups({"update", "public"})
     * @var int
     */
    protected $length;

    /**
     * Width of the box in cm
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank
     * @Assert\Range( min = 0, max = 100)
     * @JMS\Type("integer")
     * @JMS\Expose
     * @JMS\Groups({"update", "public"})
     * @var int
     */
    protected $width;

    /**
     * Height of the box in cm
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank
     * @Assert\Range( min = 0, max = 100)
     * @JMS\Since("v2")
     * @JMS\Type("integer")
     * @JMS\Expose
     * @JMS\Groups({"update", "public"})
     * @var int
     */
    protected $height;

    /**
     * Date and time of the latest update of the status of this box
     *
     * @Assert\NotBlank(groups={"list", "public"})
     * @JMS\Type("DateTime")
     * @JMS\Expose
     * @JMS\Groups({"list", "public"})
     * @var \DateTime
     */
    protected $last_updated;

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
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return \DateTime
     */
    public function getLastUpdated()
    {
        return $this->last_updated;
    }

    /**
     * @param \DateTime $last_updated
     */
    public function setLastUpdated($last_updated)
    {
        $this->last_updated = $last_updated;
    }

    /**
     * Returns the name of the API method that gets this entity
     * @return string
     */
    public function getApiGetterMethod()
    {
        return 'getBox';
    }

    /**
     * Returns an array with the parameters that identify this entity
     * @return array
     */
    public function getIdParameters()
    {
        return array('id');
    }
}