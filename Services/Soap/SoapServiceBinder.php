<?php

namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapBundle\ServiceBinding\MessageBinderInterface;
use BeSimple\SoapBundle\ServiceDefinition\Definition;
use BeSimple\SoapBundle\Soap\SoapHeader;
use Smartbox\ApiBundle\DependencyInjection\Configuration;
use Smartbox\ApiBundle\Services\ApiConfigurator;

class SoapServiceBinder
{
    /**
     * @var Definition
     */
    private $definition;

    /**
     * @var \BeSimple\SoapBundle\ServiceBinding\MessageBinderInterface
     */
    private $requestHeaderMessageBinder;

    /**
     * @var \BeSimple\SoapBundle\ServiceBinding\MessageBinderInterface
     */
    private $requestMessageBinder;

    /**
     * @var \BeSimple\SoapBundle\ServiceBinding\MessageBinderInterface
     */
    private $responseMessageBinder;

    /** @var ApiConfigurator */
    protected $apiConfigurator;

    public function __construct(
        ApiConfigurator $configurator,
        Definition $definition,
        MessageBinderInterface $requestHeaderMessageBinder,
        MessageBinderInterface $requestMessageBinder,
        MessageBinderInterface $responseMessageBinder
    ) {
        $this->apiConfigurator = $configurator;
        $this->definition = $definition;

        $this->requestHeaderMessageBinder = $requestHeaderMessageBinder;
        $this->requestMessageBinder = $requestMessageBinder;

        $this->responseMessageBinder = $responseMessageBinder;
    }

    /**
     * @return mixed
     */
    public function getApiConfigurator()
    {
        return $this->apiConfigurator;
    }

    /**
     * @param mixed $apiConfigurator
     */
    public function setApiConfigurator($apiConfigurator)
    {
        $this->apiConfigurator = $apiConfigurator;
    }

    /**
     * @param string $method
     * @param string $header
     *
     * @return bool
     */
    public function isServiceHeader($method, $header)
    {
        return $this->definition->getMethod($method)->getHeader($header);
    }

    /**
     * @param $string
     *
     * @return bool
     */
    public function isServiceMethod($method)
    {
        return null !== $this->definition->getMethod($method);
    }

    /**
     * @param string $method
     * @param string $header
     * @param mixed  $data
     *
     * @return SoapHeader
     */
    public function processServiceHeader($method, $header, $data)
    {
        $methodDefinition = $this->definition->getMethod($method);
        $headerDefinition = $methodDefinition->getHeader($header);

        $this->requestHeaderMessageBinder->setHeader($header);
        $data = $this->requestHeaderMessageBinder->processMessage(
            $methodDefinition,
            $data,
            $this->definition->getTypeRepository()
        );

        return new SoapHeader($this->definition->getNamespace(), $headerDefinition->getName(), $data);
    }

    /**
     * @param string $name
     * @param mixed
     *
     * @return mixed
     */
    public function processServiceMethodReturnValue($name, $return)
    {
        $methodDefinition = $this->definition->getMethod($name);

        return $this->responseMessageBinder->processMessage(
            $methodDefinition,
            $return,
            $this->definition->getTypeRepository()
        );
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return array
     */
    public function processServiceMethodArguments($method, $arguments)
    {
        /** @var Definition $methodDefinition */
        $methodDefinition = $this->definition->getMethod($method);

        $serviceId = $this->definition->getName();
        $serviceConfig = $this->apiConfigurator->getConfig($serviceId);
        $methodName = $methodDefinition->getName();
        $methodConfig = $this->apiConfigurator->getConfig($serviceId, $methodName);

        $input = $this->requestMessageBinder->processMessage(
            $methodDefinition,
            $arguments,
            $this->definition->getTypeRepository()
        );

        $defaults = $methodConfig['defaults'];

        // Extract filters
        if (array_key_exists('filters', $input)) {
            foreach ($methodConfig[ApiConfigurator::INPUT] as $inputName => $inputConfig) {
                if (Configuration::MODE_FILTER == $inputConfig['mode']) {
                    $value = null;

                    if (array_key_exists($inputName, $input['filters'])) {
                        $value = $input['filters'][$inputName];
                    } // Use default values for filters
                    elseif (array_key_exists($inputName, $defaults)) {
                        $value = $defaults[$inputName];
                    }

                    if (!empty($value)) {
                        $type = $inputConfig['type'];
                        $input[$inputName] = $this->apiConfigurator->getCleanParameter($inputName, $type, $value);
                    }
                }
            }

            unset($input['filters']);
        }

        $arguments = array_merge(
            $defaults,
            [
                '_controller' => $methodDefinition->getController(),
                ApiConfigurator::METHOD_NAME => $methodName,
                ApiConfigurator::METHOD_CONFIG => $methodConfig,
                ApiConfigurator::SERVICE_ID => $serviceId,
                ApiConfigurator::SERVICE_NAME => $serviceConfig['name'],
                ApiConfigurator::VERSION => $serviceConfig['version'],
                ApiConfigurator::INPUT => $input,
            ]
        );

        return $arguments;
    }
}
