<?php

namespace Smartbox\ApiBundle\Entity;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints as Assert;

class Location extends ApiEntity implements HeaderInterface
{

    /**
     * @JMS\Type("string")
     * @JMS\Exclude
     * @var LocatableEntity
     */
    protected $entity;

    /**
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"public"})
     * @var string
     */
    protected $api_service;

    /**
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"public"})
     * @var string
     */
    protected $api_method;

    /**
     * Usual parameters to identify the entity (Usually id)
     * @Assert\NotBlank
     * @JMS\Type("array<string, string>")
     * @JMS\Groups({"public"})
     * @JMS\Accessor(getter="getParametersAsArray",setter="setParameters")
     * @var array
     */
    protected $parameters;

    /**
     * @JMS\Type("string")
     * @JMS\Exclude
     * @var string
     */
    protected $url;

    /**
     * @JMS\Type("string")
     * @JMS\Exclude
     * @var bool
     */
    protected $resolved = false;

    public function getHeaderName()
    {
        return 'Location';
    }

    public function __construct(LocatableEntity $entity = null)
    {
        $this->parameters = array();

        if ($entity) {
            $this->setEntity($entity);
        }
    }

    public function setEntity(LocatableEntity $entity)
    {
        $accessor = new PropertyAccessor();

        foreach ($entity->getIdParameters() as $param) {
            if ($accessor->isReadable($entity, $param)) {
                $value = $accessor->getValue($entity, $param);
                $this->addParameter($param, $value);
            } else {
                throw new \Exception(
                    "Parameter $param returned by getIdParameters by class "
                    .$entity->getType()
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
     * @return boolean
     */
    public function isResolved()
    {
        return !empty($this->url) && !empty($this->api_service) && !empty($this->api_method);
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
        $res = array();
        /**
         * @var string $key
         * @var KeyValue $param
         */
        foreach ($this->parameters as $key => $param) {
            $res[$key] = $param->getValue();
        }

        return $res;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters)
    {
        foreach($parameters as $key => $value){
            $this->addParameter($key,$value);
        }
    }

    public function getParameter($key)
    {
        $param = $this->parameters[$key];
        if ($param) {
            return $param->getValue();
        } else {
            return null;
        }
    }

    public function addParameter($key, $value)
    {
        $value = strval($value);
        $param = new KeyValue($key, $value);
        $this->parameters[$key] = $param;
    }

    public function getRESTHeaderValue()
    {
        return $this->url;
    }
}