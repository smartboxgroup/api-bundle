<?php

namespace Smartbox\ApiBundle\Services;

use Metadata\MetadataFactoryInterface;
use Smartbox\ApiBundle\DependencyInjection\Configuration;
use Smartbox\ApiBundle\Entity\ApiEntity;
use Smartbox\ApiBundle\Entity\BasicResponse;
use Smartbox\CoreBundle\Type\EntityInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ApiConfigurator.
 */
class ApiConfigurator
{
    protected static $areAliasesRegistered = false;

    const SOAP_ALIASES_FILENAME = 'SoapAliases.php';

    const SERVICE_ID = 'serviceId';
    const SERVICE_NAME = 'serviceName';
    const METHOD_NAME = 'methodName';
    const VERSION = 'version';
    const METHOD_CONFIG = 'methodConfig';
    const INPUT = 'input';

    protected $cacheDir = '';

    /** @var MetadataFactoryInterface */
    protected $metadataFactory;

    /** @var array */
    protected $config;

    /** @var array */
    protected $successCodes;

    /** @var array */
    protected $errorCodes;

    /** @var array */
    protected $restEmptyBodyResponseCodes;

    /** @var string */
    protected $fixturePath;

    public static $arraySymbol = '[]';

    public static $arraySymbolSoap = '[]';

    /**
     * @return array
     */
    public function getRestEmptyBodyResponseCodes()
    {
        return $this->restEmptyBodyResponseCodes;
    }

    /**
     * @param array $restEmptyBodyResponseCodes
     */
    public function setRestEmptyBodyResponseCodes($restEmptyBodyResponseCodes)
    {
        $this->restEmptyBodyResponseCodes = $restEmptyBodyResponseCodes;
    }

    /**
     * @return string
     */
    public function getFixturePath()
    {
        return $this->fixturePath;
    }

    /**
     * @param string $fixturePath
     *
     * @return $this
     */
    public function setFixturePath($fixturePath)
    {
        $this->fixturePath = $fixturePath;

        return $this;
    }

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
    );

    protected static $jmsTypes = array(
        Configuration::INTEGER => 'integer',
        Configuration::FLOAT => 'double',
        Configuration::BOOL => 'boolean',
        Configuration::STRING => 'string',
        Configuration::DATETIME => 'DateTime',
    );

    protected $registeredAliases = array();

    public function __construct(MetadataFactoryInterface $metadataFactory, $config, $successCodes, $errorCodes, $restEmptyBodyResponseCodes, $cacheDir, $fixturePath = null)
    {
        $this->cacheDir = $cacheDir;
        $this->metadataFactory = $metadataFactory;
        $this->config = $config;
        $this->successCodes = $successCodes;
        $this->errorCodes = $errorCodes;
        $this->restEmptyBodyResponseCodes = $restEmptyBodyResponseCodes;
        $this->fixturePath = $fixturePath;
        $this->registerEntityAliases();
    }

    /**
     * @param null $serviceId
     * @param null $methodName
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getConfig($serviceId = null, $methodName = null)
    {
        if (null == $serviceId) {
            return $this->config;
        } else {
            if (null == $methodName) {
                return $this->config[$serviceId];
            } else {
                if (!array_key_exists($methodName, $this->config[$serviceId]['methods'])) {
                    throw new \Exception("Missing configuration for api $methodName");
                }

                return $this->config[$serviceId]['methods'][$methodName];
            }
        }
    }

    /**
     * @param string $serviceName
     * @param string $version
     * @param string $methodName
     *
     * @return array|null
     */
    public function getConfigByServiceNameVersionAndMethod($serviceName, $version, $methodName)
    {
        foreach ($this->config as $serviceId => $serviceConf) {
            if ($serviceConf['name'] === $serviceName && $serviceConf['version'] === $version) {
                foreach ($serviceConf['methods'] as $currentMethodName => $methodConf) {
                    if ($currentMethodName === $methodName) {
                        return $methodConf;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param string $serviceName
     * @param string $version
     *
     * @return array
     */
    public function getConfigByServiceNameAndVersion($serviceName, $version)
    {
        $configuration = [];
        foreach ($this->config as $serviceId => $serviceConf) {
            if ($serviceConf['name'] === $serviceName && $serviceConf['version'] === $version) {
                foreach ($serviceConf['methods'] as $currentMethodName => $methodConf) {
                    $configuration[$currentMethodName] = $methodConf;
                }
            }
        }

        return $configuration;
    }

    public function getRestRouteNameFor($serviceId, $methodName)
    {
        return "smartapi.rest.$serviceId.$methodName";
    }

    /**
     * Returns true if a service with $id is defined.
     *
     * @param $id
     *
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
     * Register class aliases for all combinations of entity/group that appear on the configuration.
     *
     * @throws \Exception
     */
    protected function registerEntityAliases()
    {
        $cacheFile = $this->cacheDir.DIRECTORY_SEPARATOR.self::SOAP_ALIASES_FILENAME;

        if (file_exists($cacheFile)) {
            if (self::$areAliasesRegistered) {
                return;
            }

            include_once $cacheFile;
        } else {
            $this->registerEntityGroupAlias(BasicResponse::class, ApiEntity::GROUP_PUBLIC);

            foreach ($this->config as $service => $serviceConfig) {
                foreach ($serviceConfig['methods'] as $method => $methodConfig) {
                    foreach ($methodConfig[ApiConfigurator::INPUT] as $input => $inputConfig) {
                        $mode = $inputConfig['mode'];
                        $class = $inputConfig['type'];
                        $group = $inputConfig['group'];
                        if (Configuration::MODE_BODY == $mode && $class && $group) {
                            $this->registerEntityGroupAlias($class, $group);
                        }
                    }
                    if (array_key_exists('output', $methodConfig)) {
                        $outputConfig = $methodConfig['output'];
                        $class = $outputConfig['type'];
                        $group = $outputConfig['group'];
                        if ($class && $group) {
                            $this->registerEntityGroupAlias($class, $group);
                        }
                    }
                }
            }

            $contents = "<?php \n";

            foreach ($this->registeredAliases as $alias => $class) {
                $contents .= "class_alias('$class', '$alias');\n";
            }

            file_put_contents($cacheFile, $contents);
        }

        self::$areAliasesRegistered = true;
    }

    /**
     * Creates and registers an alias for a given $type and $group.
     *
     * The alias name will follow the convention typeGroup
     *
     * @param $class
     * @param $group
     *
     * @throws \Exception
     */
    public function registerEntityGroupAlias($class, $group)
    {
        if (empty($class) || !is_string($class)) {
            throw new \InvalidArgumentException('Invalid value for argument type');
        }

        if (empty($group) || !is_string($group)) {
            throw new \InvalidArgumentException('Invalid value for argument group');
        }

        $class = str_replace(self::$arraySymbol, '', $class);
        $alias = $class.ucfirst($group);

        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Class $class doesn't exists");
        }

        if (class_exists($alias) && !is_a($alias, $class, true)) {
            throw new \Exception("Class $alias already exists and is not an alias of $class");
        }

        if (!$this->isRegisteredAlias($alias)) {
            if (!class_exists($alias)) {
                class_alias($class, $alias);
            }

            $this->registeredAliases[$alias] = $class;

            // ITERATE OVER CLASS TO DETECT SUB-ENTITIES AND CREATE THE GROUP ALIAS FOR THEM

            // Get the metadata for the current class
            $metadata = $this->metadataFactory->getMetadataForClass($class);

            // For every property in the class, check if is an array
            //    if it s an array get the subtype and call the method recursively with the subtype
            if (null !== $metadata) {
                foreach ($metadata->propertyMetadata as $item) {
                    $type = $item->type;

                    // if the sub-element is an entity register it using the parent group
                    if (self::isEntity($type['name'])) {
                        $this->registerEntityGroupAlias($type['name'], $group);
                    } elseif ('array' === $type['name']) {
                        // otherwise if the sub-element is an array (of entities) check the first two indexes in the params
                        // attribute to determine the type(s) of the array items and register them as well using the parent
                        // group
                        foreach (range(0, 1) as $i) {
                            if (isset($type['params'][$i]) && self::isEntity($type['params'][$i]['name'])) {
                                $this->registerEntityGroupAlias($type['params'][$i]['name'], $group);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns true if the given $alias is registered.
     *
     * @param $alias
     *
     * @return bool
     */
    public function isRegisteredAlias($alias)
    {
        return array_key_exists($alias, $this->registeredAliases);
    }

    /**
     * Return the original class name of a registered $alias.
     *
     * @param string $alias
     *
     * @return string
     */
    public function getAliasOriginalType($alias)
    {
        return $this->registeredAliases[$alias];
    }

    /**
     * @param $key string
     *
     * @return mixed
     */
    public function getSuccessCodeDescription($key)
    {
        if (!isset($this->successCodes[$key])) {
            throw new \OutOfRangeException('Requested code does not exist');
        }
        return $this->successCodes[$key];
    }

    /**
     * Sets the possible success codes as an associative array of strings successCode => message.
     *
     * @param array $successCodes
     */
    public function setSuccessCodes($successCodes)
    {
        if (!is_array($successCodes)) {
            throw new \InvalidArgumentException('Invalid argument successCodes');
        } else {
            foreach ($successCodes as $key => $value) {
                if (empty($key) || empty($value) || !is_string($value)) {
                    throw new \InvalidArgumentException('Invalid argument successCodes');
                }
            }
        }

        $this->successCodes = $successCodes;
    }

    /**
     * Returns the possible error codes and their description messages as an associative array.
     *
     * @return array
     */
    public function getErrorCodes()
    {
        return $this->errorCodes;
    }

    /**
     * Sets the possible error codes as an associative array of strings errorCode => errorMessage.
     *
     * @param array $errorCodes
     */
    public function setErrorCodes($errorCodes)
    {
        if (!is_array($errorCodes)) {
            throw new \InvalidArgumentException('Invalid argument errorCodes');
        } else {
            foreach ($errorCodes as $key => $value) {
                if (empty($key) || empty($value) || !is_string($value)) {
                    throw new \InvalidArgumentException('Invalid argument errorCodes');
                }
            }
        }

        $this->errorCodes = $errorCodes;
    }

    /**
     * Returns the equivalent BeSimpleSoap type to the given configuration or jms type.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getSoapTypeFor($type)
    {
        if (empty($type)) {
            return null;
        }

        if (!is_string($type)) {
            throw new \InvalidArgumentException('Expected string as an argument');
        }

        if (false === strpos($type, self::$arraySymbol)) {
            if (array_key_exists($type, self::$typeToSoap)) {
                return self::$typeToSoap[$type];
            } else {
                return $type;
            }
        } else {
            $typeSingle = str_replace(self::$arraySymbol, '', $type);

            return self::getSoapTypeFor($typeSingle).self::$arraySymbolSoap;
        }
    }

    /**
     * Returns true if the given $elementType is the name of a class implementing the EntityInterface.
     *
     * @param string $elementType
     *
     * @return bool
     */
    public static function isEntity($elementType)
    {
        if (!is_string($elementType)) {
            throw new \InvalidArgumentException('Expected string as an argument');
        }

        $isEntityClass =
            class_exists($elementType)
            && is_a($elementType, EntityInterface::class, true)
        ;

        return $isEntityClass;
    }

    /**
     * Returns the single type if an array type is passed.
     *
     * @param $confType
     *
     * @return mixed
     */
    public static function getSingleType($confType)
    {
        if (!is_string($confType)) {
            throw new \InvalidArgumentException('Expected string as an argument');
        }

        $elementType = str_replace(self::$arraySymbol, '', $confType);

        return $elementType;
    }

    /**
     * Returns the JMS equivalent type to the given configuration type, if the given $confType is an array, the single type
     * is returned and not the array.
     *
     * @param string $confType
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function getJMSSingleType($confType)
    {
        if (!is_string($confType)) {
            throw new \InvalidArgumentException('Expected string as an argument');
        }

        $elementType = str_replace(self::$arraySymbol, '', $confType);

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
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public static function isEntityOrArrayOfEntities($type)
    {
        if (!is_string($type)) {
            throw new \InvalidArgumentException('Expected string as an argument');
        }

        $elementType = str_replace(self::$arraySymbol, '', $type);

        return self::isEntity($elementType);
    }

    /**
     * This function returns the JMS equivalent type to the given $confType.
     *
     * If $confType is not 'string', 'integer', 'float', 'datetime' or 'bool' and is not an instance of EntityInterface,
     * it throws an \InvalidArgumentException
     *
     * @param string $confType
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public static function getJMSType($confType)
    {
        if (!is_string($confType)) {
            throw new \InvalidArgumentException('Expected string as an argument');
        }

        $isArray = false !== strpos($confType, self::$arraySymbol);
        $elementType = str_replace(self::$arraySymbol, '', $confType);

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
     * Otherwise it throws an exception.
     *
     * If $type is not 'integer', 'float', 'datetime' or 'bool' it is ignored and $value is returned
     *
     * @param $inputName
     * @param string $type
     * @param $value
     *
     * @return bool|\DateTime|float|int
     *
     * @throws \Exception
     */
    public function getCleanParameter($inputName, $type, $value)
    {
        if (!is_string($type)) {
            throw new \InvalidArgumentException('Expected string as an argument');
        }

        switch ($type) {
            case Configuration::STRING:
                if (!is_string($value)) {
                    $actualType = gettype($value);
                    throw new BadRequestHttpException("Parameter $inputName is not string. $actualType given.");
                }
                $param = $value;
                break;
            case Configuration::INTEGER:
                if (!is_numeric($value)) {
                    throw new BadRequestHttpException("Parameter $inputName with value $value is not numeric ");
                }
                $param = intval($value);
                break;
            case Configuration::FLOAT:
                if (!is_numeric($value)) {
                    throw new BadRequestHttpException("Parameter $inputName with value $value is not numeric");
                }
                $param = floatval($value);
                break;
            case Configuration::DATETIME:
                try {
                    $param = new \DateTime($value);
                } catch (\Exception $e) {
                    throw new BadRequestHttpException("Parameter $inputName with value $value doesn't have a valid date format");
                }
                break;
            case Configuration::BOOL:
                if (in_array($value, array('1', 'true', 1, true), true)) {
                    $param = true;
                } elseif (in_array($value, array('0', 'false', 0, false), true)) {
                    $param = false;
                } else {
                    throw new BadRequestHttpException("Parameter $inputName with value $value doesn't have a valid bool");
                }
                break;
            default:
                $param = $value;
                break;
        }

        return $param;
    }
}
