<?php

namespace Smartbox\ApiBundle\Services;

use Smartbox\ApiBundle\DependencyInjection\Configuration;
use Smartbox\ApiBundle\Entity\ApiEntity;
use Smartbox\ApiBundle\Entity\BasicResponse;
use Smartbox\ApiBundle\Entity\HeaderInterface;
use Smartbox\CoreBundle\Entity\EntityInterface;
use Smartbox\Integration\FrameworkBundle\Processors\Itinerary;


class ApiConfigurator
{
    /** @var  array */
    protected $config;

    /** @var  array */
    protected $successCodes;

    /** @var  array */
    protected $errorCodes;

    public static $arraySymbol = '[]';

    public static $arraySymbolSoap = '[]';

    public static $typeToSoap = array(
        'int' => 'int',
        'integer' => 'int',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'float' => 'float',
        'double' => 'float',
        'DateTime' => 'dateTime',
        'date' => 'dateTime',
        'datetime' => 'dateTime',
        'key_value' => 'BeSimple\SoapCommon\Type\KeyValue\String',
        'array' => 'BeSimple\SoapCommon\Type\KeyValue\String[]',
    );

    protected static $jmsTypes = array(
        Configuration::INTEGER => 'integer',
        Configuration::FLOAT => 'double',
        Configuration::BOOL => 'boolean',
        Configuration::STRING => 'string',
        Configuration::DATETIME => 'DateTime',
    );

    protected $registeredAliases = array();

    function __construct($config, $successCodes, $errorCodes)
    {
        $this->config = $config;
        $this->successCodes = $successCodes;
        $this->errorCodes = $errorCodes;
        $this->registerEntityAliases();
    }

    /**
     * @param null $serviceId
     * @param null $methodName
     * @return array
     * @throws \Exception
     */
    public function getConfig($serviceId = null, $methodName = null)
    {
        if ($serviceId == null) {
            return $this->config;
        } else {
            if ($methodName == null) {
                return $this->config[$serviceId];
            } else {
                if (!array_key_exists($methodName, $this->config[$serviceId]['methods'])) {
                    throw new \Exception("Missing configuration for api $methodName");
                }

                return $this->config[$serviceId]['methods'][$methodName];
            }
        }
    }

    public function getRestRouteNameFor($serviceId, $methodName)
    {
        return "smartapi.rest.$serviceId.$methodName";
    }

    /**
     * Returns true if a service with $id is defined.
     * @param $id
     * @return bool
     */
    public function hasService($id)
    {
        return array_key_exists($id, $this->config);
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
        $this->registerEntityAliases();
    }

    /**
     * Register class aliases for all combinations of entity/group that appear on the configuration
     *
     * @throws \Exception
     */
    protected function registerEntityAliases()
    {
        $this->registerEntityGroupAlias(BasicResponse::class, ApiEntity::GROUP_PUBLIC);

        foreach ($this->config as $service => $serviceConfig) {
            foreach ($serviceConfig['methods'] as $method => $methodConfig) {
                foreach ($methodConfig['input'] as $input => $inputConfig) {
                    $mode = $inputConfig['mode'];
                    $type = $inputConfig['type'];
                    $group = $inputConfig['group'];
                    if ($mode == Configuration::MODE_BODY && $type && $group) {
                        $this->registerEntityGroupAlias($type, $group);
                    }
                }
                if (array_key_exists('output', $methodConfig)) {
                    $outputConfig = $methodConfig['output'];
                    $type = $outputConfig['type'];
                    $group = $outputConfig['group'];
                    if ($type && $group) {
                        $this->registerEntityGroupAlias($type, $group);
                    }
                }
            }
        }
    }

    /**
     * Creates and registers an alias for a given $type and $group
     *
     * The alias name will follow the convention typeGroup
     *
     * @param $type
     * @param $group
     * @throws \Exception
     */
    public function registerEntityGroupAlias($type, $group)
    {
        if (empty($type) || !is_string($type)) {
            throw new \InvalidArgumentException("Invalid value for argument type");
        }

        if (empty($group) || !is_string($group)) {
            throw new \InvalidArgumentException("Invalid value for argument group");
        }

        $type = str_replace(self::$arraySymbol, "", $type);
        $alias = $type.ucfirst($group);

        if (!class_exists($type)) {
            throw new \InvalidArgumentException("Class $type doesn't exists");
        }

        if (class_exists($alias) && !is_a($alias, $type, true)) {
            throw new \Exception("Class $alias already exists and is not an alias of $type");
        }

        if (!$this->isRegisteredAlias($alias)) {
            if (!class_exists($alias)) {
                class_alias($type, $alias);
            }

            $this->registeredAliases[$alias] = $type;
        }
    }

    /**
     * Returns true if the given $alias is registered
     *
     * @param $alias
     * @return bool
     */
    public function isRegisteredAlias($alias)
    {
        return array_key_exists($alias, $this->registeredAliases);
    }

    /**
     * Return the original class name of a registered $alias
     *
     * @param string $alias
     * @return string
     */
    public function getAliasOriginalType($alias)
    {
        return $this->registeredAliases[$alias];
    }

    /**
     * @param $key string
     * @return mixed
     */
    public function getSuccessCodeDescription($key)
    {
        return $this->successCodes[$key];
    }

    /**
     * Sets the possible success codes as an associative array of strings successCode => message
     *
     * @param array $successCodes
     */
    public function setSuccessCodes($successCodes)
    {
        if (!is_array($successCodes)) {
            throw new \InvalidArgumentException("Invalid argument successCodes");
        } else {
            foreach ($successCodes as $key => $value) {
                if (empty($key) || empty($value) || !is_string($value)) {
                    throw new \InvalidArgumentException("Invalid argument successCodes");
                }
            }
        }

        $this->successCodes = $successCodes;
    }

    /**
     * Returns the possible error codes and their description messages as an associative array
     *
     * @return array
     */
    public function getErrorCodes()
    {
        return $this->errorCodes;
    }

    /**
     * Sets the possible error codes as an associative array of strings errorCode => errorMessage
     *
     * @param array $errorCodes
     */
    public function setErrorCodes($errorCodes)
    {
        if (!is_array($errorCodes)) {
            throw new \InvalidArgumentException("Invalid argument errorCodes");
        } else {
            foreach ($errorCodes as $key => $value) {
                if (empty($key) || empty($value) || !is_string($value)) {
                    throw new \InvalidArgumentException("Invalid argument errorCodes");
                }
            }
        }

        $this->errorCodes = $errorCodes;
    }

    /**
     * Returns the equivalent BeSimpleSoap type to the given configuration or jms type
     *
     * @param string $type
     * @return string|null
     */
    public static function getSoapTypeFor($type)
    {
        if(empty($type)){
            return null;
        }

        if(!is_string($type)){
            throw new \InvalidArgumentException("Expected string as an argument");
        }

        if (strpos($type, self::$arraySymbol) === false) {
            if (array_key_exists($type, self::$typeToSoap)) {
                return self::$typeToSoap[$type];
            } else {
                return $type;
            }
        } else {
            $typeSingle = str_replace(self::$arraySymbol, "", $type);

            return self::getSoapTypeFor($typeSingle).self::$arraySymbolSoap;
        }
    }

    /**
     * Returns true if the given $type is a class implementing HeaderInterface
     *
     * @param string $elementType
     * @return bool
     */
    public static function isHeaderType($elementType)
    {
        if(!is_string($elementType)){
            throw new \InvalidArgumentException("Expected string as an argument");
        }

        $isHeaderClass =
            class_exists($elementType)
            && array_key_exists(HeaderInterface::class, class_implements($elementType));

        return $isHeaderClass;
    }

    /**
     * Returns true if the given $type is a class implementing HeaderInterface or an array of it
     *
     * @param string $type
     * @return bool
     */
    public static function isHeaderOrArrayOfHeaders($type)
    {
        if(!is_string($type)){
            throw new \InvalidArgumentException("Expected string as an argument");
        }

        $elementType = str_replace(self::$arraySymbol, "", $type);

        return self::isHeaderType($elementType);
    }

    /**
     * Returns true if the given $elementType is the name of a class implementing the EntityInterface
     *
     * @param string $elementType
     * @return bool
     */
    public static function isEntity($elementType)
    {
        if(!is_string($elementType)){
            throw new \InvalidArgumentException("Expected string as an argument");
        }

        $isEntityClass =
            class_exists($elementType)
            && array_key_exists(EntityInterface::class, class_implements($elementType));

        return $isEntityClass;
    }

    /**
     * Returns the single type if an array type is passed
     *
     * @param $confType
     * @return mixed
     */
    public static function getSingleType($confType)
    {
        if(!is_string($confType)){
            throw new \InvalidArgumentException("Expected string as an argument");
        }

        $elementType = str_replace(self::$arraySymbol, "", $confType);

        return $elementType;
    }

    /**
     * Returns the JMS equivalent type to the given configuration type, if the given $confType is an array, the single type
     * is returned and not the array.
     *
     * @param string $confType
     * @return string
     * @throws \Exception
     */
    public static function getJMSSingleType($confType)
    {
        if(!is_string($confType)){
            throw new \InvalidArgumentException("Expected string as an argument");
        }

        $elementType = str_replace(self::$arraySymbol, "", $confType);

        if (array_key_exists($elementType, self::$jmsTypes)) {
            $elementType = self::$jmsTypes[$elementType];
        } elseif (!self::isEntity($elementType)) {
            throw new \Exception("$confType is not a valid type");
        }

        return $elementType;
    }

    /**
     * This function returns true if the given $type is a class implementing EntityInterface or is an array of those.
     *
     * @param string $type
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function isEntityOrArrayOfEntities($type)
    {
        if(!is_string($type)){
            throw new \InvalidArgumentException("Expected string as an argument");
        }

        $elementType = str_replace(self::$arraySymbol, "", $type);

        return self::isEntity($elementType);
    }

    /**
     * This function returns the JMS equivalent type to the given $confType
     *
     * If $confType is not 'string', 'integer', 'float', 'datetime' or 'bool' and is not an instance of EntityInterface,
     * it throws an \InvalidArgumentException
     *
     * @param string $confType
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getJMSType($confType)
    {
        if(!is_string($confType)){
            throw new \InvalidArgumentException("Expected string as an argument");
        }

        $isArray = strpos($confType, self::$arraySymbol) !== false;
        $elementType = str_replace(self::$arraySymbol, "", $confType);

        if (array_key_exists($elementType, self::$jmsTypes)) {
            $elementType = self::$jmsTypes[$elementType];
        } elseif (!self::isEntity($elementType)) {
            throw new \InvalidArgumentException("$confType is not a valid type");
        }

        if ($isArray) {
            $type = "array<$elementType>";
        } else {
            $type = $elementType;
        }

        return $type;
    }

    /**
     * This function casts the given $value to the expected $type if is possible.
     * Otherwise it throws an exception
     *
     * If $type is not 'integer', 'float', 'datetime' or 'bool' it is ignored and $value is returned
     *
     * @param $inputName
     * @param string $type
     * @param $value
     * @return bool|\DateTime|float|int
     * @throws \Exception
     */
    public function getCleanParameter($inputName, $type, $value)
    {
        if(!is_string($type)){
            throw new \InvalidArgumentException("Expected string as an argument");
        }

        switch ($type) {
            case Configuration::INTEGER:
                if (!is_numeric($value)) {
                    throw new \Exception("Parameter $inputName with value $value is not numeric ");
                }
                $param = intval($value);
                break;
            case Configuration::FLOAT:
                if (!is_numeric($value)) {
                    throw new \Exception("Parameter $inputName with value $value is not numeric");
                }
                $param = floatval($value);
                break;
            case Configuration::DATETIME:
                try {
                    $param = new \DateTime($value);
                } catch (\Exception $e) {
                    throw new \Exception("Parameter $inputName with value $value doesn't have a valid date format");
                }
                break;
            case Configuration::BOOL:
                if (in_array($value, array('1', 'true', 1, true), true)) {
                    $param = true;
                } elseif (in_array($value, array('0', 'false', 0, false), true)) {
                    $param = false;
                } else {
                    throw new \Exception("Parameter $inputName with value $value doesn't have a valid date format");
                }
                break;
            default:
                $param = $value;
                break;
        }

        return $param;
    }
}