<?php

namespace Smartbox\ApiBundle\Tests\Fixtures\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;
use JMS\Serializer\Annotation as JMS;
use Smartbox\ApiBundle\Entity\ApiEntity;
use Smartbox\ApiBundle\Entity\LocatableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Product
 * @package Smartbox\ApiBundle\Tests\Fixtures\Entity
 */
class Product extends ApiEntity implements LocatableEntity
{
    /**
     * Numeric id for a product.
     *
     * @Assert\Type(type="integer")
     * @Assert\NotNull(groups={"public"})
     * @JMS\Type("integer")
     * @JMS\Expose
     * @JMS\Groups({"public"})
     *
     * @var integer
     */
    protected $id;

    /**
     * Name of the product.
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min="1")
     * @Soap\ComplexType("string")
     * @JMS\Expose
     * @JMS\Groups({"product"})
     *
     * @var string
     */
    protected $name;

    /**
     * List of language codes based on the ISO 639-3
     *
     * @Assert\NotBlank
     * @JMS\Type("array<string>")
     * @JMS\Expose
     * @JMS\Groups({"list"})
     * @JMS\SerializedName("languages")
     *
     * @var array
     */
    protected $languages = [];

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getLanguages()
    {
        return $this->languages;
    }

    public function setLanguages(array $languages)
    {
        $this->languages = $languages;
    }

    /**
     * Returns the name of the API method that gets this entity
     * @return string
     */
    public function getApiGetterMethod()
    {
        return 'getProduct';
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