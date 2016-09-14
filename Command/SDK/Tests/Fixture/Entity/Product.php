<?php
namespace Smartbox\ApiRestClient\Tests\Fixture\Entity;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Product
 */
class Product
{
    /** Type constants */
    const TYPE_BOX                      = 'box';
    const TYPE_EXPERIENCE               = 'experience';

    /**
     * Get a list with all the accepted types.
     *
     * @return array
     */
    public static function getValidTypes()
    {
        return [
            self::TYPE_BOX,
            self::TYPE_EXPERIENCE,
        ];
    }
    /**
     * The unique identifier of the Product
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
     * A value representing the type of the product.
     *
     * @Assert\Type(type="string")
     * @Assert\Choice(callback="Smartbox\ApiRestClient\Tests\Fixture\Entity\Product::getValidTypes")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\SerializedName("type")
     *
     * @var string
     */
    protected $type;

    /**
     * Name of the product.
     *
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @Assert\Length(min="1",max="200")
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\SerializedName("name")
     *
     * @var string
     */
    protected $name;

    /**
     * Description of the product
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min="1",max="700")
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\SerializedName("description")
     *
     * @var string
     */
    protected $description;

    /**
     * Universe for this Product
     *
     * @Assert\Type(type="Smartbox\ApiRestClient\Tests\Fixture\Entity\Universe")
     * @Assert\Valid
     * @Assert\NotBlank
     * @JMS\Type("Smartbox\ApiRestClient\Tests\Fixture\Entity\Universe")
     * @JMS\Expose
     * @JMS\Groups({"logs"})
     * @JMS\SerializedName("universe")
     *
     * @var Universe
     */
    protected $universe;

    /**
     * @return string
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId ($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getType ()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType ($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName ($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription ()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription ($description)
    {
        $this->description = $description;
    }

    /**
     * @return Universe
     */
    public function getUniverse ()
    {
        return $this->universe;
    }

    /**
     * @param Universe $universe
     */
    public function setUniverse ($universe)
    {
        $this->universe = $universe;
    }


}
