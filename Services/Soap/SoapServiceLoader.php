<?php
namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapBundle\ServiceDefinition as Definition;
use BeSimple\SoapBundle\ServiceDefinition\Annotation;
use BeSimple\SoapCommon\Definition\Type\ComplexType;
use BeSimple\SoapCommon\Definition\Type\TypeRepository;
use Smartbox\ApiBundle\DependencyInjection\Configuration;
use Smartbox\ApiBundle\Entity\BasicResponse;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Smartbox\CoreBundle\Entity\EntityInterface;
use Symfony\Component\Config\Loader\Loader;


class SoapServiceLoader extends Loader
{

    /** @var  TypeRepository */
    protected $typeRepository;

    /** @var  ApiConfigurator */
    protected $apiConfigurator;

    /**
     * @param ApiConfigurator $configurator
     * @param TypeRepository $typeRepository
     */
    public function __construct(ApiConfigurator $configurator, TypeRepository $typeRepository)
    {
        $this->apiConfigurator = $configurator;
        $this->typeRepository = $typeRepository;
    }


    /**
     *
     * Load soap web services based on the APIs configuration
     *
     * @param mixed $resource
     * @param null $type
     *
     * @return Definition\Definition
     * @throws \Exception
     */
    public function load($resource, $type = null)
    {
        $serviceDefinition = new Definition\Definition($this->typeRepository);
        $serviceDefinition->setName($resource);
        $serviceConfig = $this->apiConfigurator->getConfig($resource);
        $serviceVersion = $serviceConfig['version'];

        foreach ($serviceConfig['methods'] as $methodName => $methodConfig) {
            $methodHeaders = array();
            $methodArguments = array();
            $soapMethodName = $methodName;
            $methodReturnType = null;
            $methodReturnGroup = null;

            $filtersPresent = false;

            // Input
            foreach ($methodConfig['input'] as $paramName => $paramConfig) {
                $mode = $paramConfig['mode'];
                $type = $paramConfig['type'];
                $group = $paramConfig['group'];

                switch ($mode) {
                    case Configuration::MODE_FILTER:
                        $filtersPresent = true;
                        break;
                    case Configuration::MODE_REQUIREMENT:
                        $methodArguments[$paramName] = $this->loadType($type, null, $serviceVersion);
                        break;
                    case Configuration::MODE_BODY:
                        $methodArguments[$paramName] = $this->loadType($type, $group, $serviceVersion);
                        break;
                }
            }

            // Filters
            if ($filtersPresent) {
                $methodArguments['filters'] = $this->loadType(
                    'BeSimple\SoapCommon\Type\KeyValue\String[]',
                    null,
                    $serviceVersion
                );
            }

            // Output
            if (!array_key_exists('output', $methodConfig)) {
                $methodReturnType = BasicResponse::class;
                $methodReturnGroup = EntityInterface::GROUP_PUBLIC;
            } else {
                $methodReturnType = $methodConfig['output']['type'];
                $methodReturnGroup = @$methodConfig['output']['group'];
            }

            if (array_key_exists('soap', $methodConfig)) {
                // Headers
                if (array_key_exists('headers', $methodConfig['soap'])) {
                    $headers = $methodConfig['soap']['headers'];
                    foreach ($headers as $hName => $hConfig) {
                        $headerType = $hConfig['type'];
                        $headerGroup = @$hConfig['group'];

                        $methodArguments[$hName] = $this->loadType($headerType, $headerGroup, $serviceVersion);
                    }
                }
            }

            // Construction
            $soapMethod = new Definition\Method(
                $soapMethodName,
                $methodConfig['controller']
            );

            if (!$soapMethod && (!empty($methodArguments) || $methodReturnType)) {
                throw new \LogicException(sprintf('@Soap\Method non-existent for "%s".', $soapMethodName));
            }

            if ($soapMethod) {
                foreach ($methodHeaders as $name => $type) {
                    $soapMethod->addHeader($name, $type);
                }

                foreach ($methodArguments as $name => $type) {
                    $soapMethod->addInput($name, $type);
                }

                if (!$methodReturnType) {
                    throw new \LogicException(sprintf('@Soap\Result non-existent for "%s".', $soapMethodName));
                }

                $soapMethod->setOutput($this->loadType($methodReturnType, $methodReturnGroup, $serviceVersion));

                $serviceDefinition->addMethod($soapMethod);
            }
        }

        return $serviceDefinition;

    }


    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type The resource type
     *
     * @return Boolean True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && $this->apiConfigurator->hasService($resource) && 'smartapi_soap' === $type;
    }

    /**
     * @return null
     */
    public function getResolver()
    {
    }

    protected function loadType($phpType, $group, $version)
    {
        $phpType = ApiConfigurator::getSoapTypeFor($phpType);

        $phpTypeBasic = $phpType;
        $arrayOf = $this->typeRepository->getArrayOf($phpType);
        $suffix = '';

        if (false !== $arrayOf) {
            $phpTypeBasic = $arrayOf;
            $this->loadType($phpTypeBasic, $group, $version);
            $suffix = TypeRepository::ARRAY_SUFFIX;
        }

        if (ApiConfigurator::isEntity($phpTypeBasic) && $group) {
            $suffix = ucfirst($group).$suffix;
        }

        $phpType = $phpTypeBasic.$suffix;

        if (!$this->typeRepository->hasType($phpType)) {

            if (!class_exists($phpType)) {
                throw new \Exception("Class $phpType doesn't exist");
            }

            $data = array(
                'phpType' => $phpType,
                'group' => $group,
                'version' => $version
            );

            $complexTypeResolver = $this->resolve($data, 'annotation_complextype');
            if (!$complexTypeResolver) {
                throw new \Exception("Complex type loader not found");
            }

            $loaded = $complexTypeResolver->load($data);

            $complexType = new ComplexType($phpType, $phpType);

            /**
             * @var string $name
             * @var Annotation\ComplexType $property
             */
            foreach ($loaded['properties'] as $name => $property) {
                $complexType->add(
                    $name,
                    $this->loadType($property->getValue(), $group, $version),
                    $property->isNillable()
                );
            }

            $this->typeRepository->addComplexType($complexType);
        }

        return $phpType;
    }
}