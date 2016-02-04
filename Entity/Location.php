<?php

namespace Smartbox\ApiBundle\Entity;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Location
 * @package Smartbox\ApiBundle\Entity
 */
class Location extends ApiEntity implements HeaderInterface
{
    /**
     * @JMS\Type("string")
     * @var LocatableEntity
     */
    protected $entity;

    /**
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"public"})
     * @var string
     */
    protected $api_service;

    /**
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Expose
     * @JMS\Groups({"public"})
     * @var string
     */
    protected $api_method;

    /**
     * Usual parameters to identify the entity (Usually id)
     * @Assert\NotBlank
     * @JMS\Type("array<Smartbox\ApiBundle\Entity\KeyValue>")
     * @JMS\Expose
     * @JMS\Groups({"public"})
     * @var array
     */
    protected $parameters;

    /**
     * @JMS\Type("string")
     * @var string
     */
    protected $url;

    /**
     * @JMS\Type("string")
     * @var bool
     */
    protected $resolved = false;

    /**
     * @var PropertyAccessor
     * @JMS\Exclude
     */
    protected $propertyAccessor;

    /**
     * {@inhertiDoc}
     */
    public function getHeaderName()
    {
        return 'Location';
    }

    /**
     * Constructor
     *
     * @param LocatableEntity|null $entity
     * @param PropertyAccessor|null $propertyAccessor
     * @throws \Exception
     */
    public function __construct(LocatableEntity $entity = null, PropertyAccessor $propertyAccessor = null)
    {
        parent::__construct();
        $this->parameters = [];

        if (null === $propertyAccessor) {
            $propertyAccessor = new PropertyAccessor();
        }
        $this->propertyAccessor = $propertyAccessor;

        if ($entity) {
            $this->setEntity($entity);
        }
    }

    public function setEntity(LocatableEntity $entity)
    {
        foreach ($entity->getIdParameters() as $param) {
            if ($this->propertyAccessor->isReadable($entity, $param)) {
                $value = $this->propertyAccessor->getValue($entity, $param);
                $this->addParameter(new KeyValue($param,$value));
            } else {
                throw new \Exception(
                    "Parameter $param returned by getIdParameters by class "
                    .$entity->getInternalType()
                    ." is not readable"
                );
            }
        }

        $this->setApiMethod($entity->getApiGetterMethod());
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getApiService()
    {
        return $this->api_service;
    }

    /**
     * @param string $api_service
     */
    public function setApiService($api_service)
    {
        $this->api_service = $api_service;
    }

    /**
     * @return string
     */
    public function getApiMethod()
    {
        return $this->api_method;
    }

    /**
     * @param string $api_method
     */
    public function setApiMethod($api_method)
    {
        $this->api_method = $api_method;
    }

    /**
     * @return boolean
     */
    public function isResolved()
    {
        return !empty($this->url) && !empty($this->api_service) && !empty($this->api_method);
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getParametersAsArray()
    {
        $res =[];

        /**
         * @var string $key
         * @var KeyValue $param
         */
        foreach ($this->parameters as $param) {
            $res[$param->getKey()] = $param->getValue();
        }

        return $res;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = [];
        foreach($parameters as $parameter) {
            if (!$parameter instanceof KeyValue) {
                throw new \InvalidArgumentException('One or more parameters are not "Smartbox\ApiBundle\Entity\KeyValue" instances');
            }
            $this->addParameter($parameter);
        }
    }

    /**
     * @param KeyValue $param
     */
    public function addParameter(KeyValue $param)
    {
        $this->parameters[] = $param;
    }

    /**
     * @return string
     */
    public function getRESTHeaderValue()
    {
        return $this->url;
    }
}