<?php

namespace Smartbox\ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use JMS\Serializer\SerializationContext;
use Smartbox\ApiBundle\DependencyInjection\Configuration;
use Smartbox\ApiBundle\Entity\BasicResponse;
use Smartbox\ApiBundle\Entity\HeaderInterface;
use Smartbox\ApiBundle\Entity\Location;
use Smartbox\ApiBundle\Entity\OK;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Smartbox\CoreBundle\Entity\EntityInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class APIController extends FOSRestController
{
    /**
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     * @return \Smartbox\CoreBundle\Validation\ValidatorWithExclusion
     */
    protected function getValidator()
    {
        return $this->get('validator');
    }

    /**
     * @return \Smartbox\CoreBundle\Hydrator\GroupVersionHydrator
     */
    protected function getGroupVersionHydrator()
    {
        return $this->get('smartcore.hydrator.group_version');
    }

    protected function throwInputValidationErrors(ConstraintViolationListInterface $list)
    {
        if (count($list) > 0) {
            $message = "Bad Request;\n ";
            /** @var ConstraintViolationInterface $error */
            foreach ($list as $error) {
                $message .= $error->getPropertyPath()." : ".$error->getMessage().";\n";
            }

            throw new BadRequestHttpException($message);
        }
    }

    protected function throwOutputValidationErrors(ConstraintViolationListInterface $list)
    {
        if (count($list) > 0) {
            $message = "Internal server error;\n ";
            /** @var ConstraintViolationInterface $error */
            foreach ($list as $error) {
                $message .= $error->getPropertyPath()." : ".$error->getMessage().";\n";
            }

            throw new \Exception($message);
        }
    }

    protected function checkAuthorization()
    {
        $configurator = $this->get('smartapi.configurator');
        $request = $this->getRequest();
        $serviceId = $request->get('serviceId');
        $methodName = $request->get('methodName');
        $config = $configurator->getConfig($serviceId, $methodName);
        $roles = $config['roles'];

        if (false === $this->get('security.authorization_checker')->isGranted($roles)) {
            throw new  AccessDeniedHttpException("Access denied");
        }
    }

    protected function prepareInput($version, $inputsConfig, $inputValues){
        foreach ($inputsConfig as $inputName => $inputConfig) {
            if ($inputConfig['mode'] == Configuration::MODE_BODY) {
                if (!array_key_exists($inputName, $inputValues)) {
                    throw new BadRequestHttpException("Missing required input: $inputName");
                }
                $body = $inputValues[$inputName];
                $group = $inputConfig['group'];
                $this->getGroupVersionHydrator()->hydrate($body, $version, $group);
            }
        }
    }

    protected function checkInput($version, $inputsConfig, $inputValues)
    {
        foreach ($inputsConfig as $inputName => $inputConfig) {
            $mode = $inputConfig['mode'];
            $expectedInputType = $inputConfig['type'];
            $errors = array();

            if ($mode == Configuration::MODE_BODY) {
                if (!array_key_exists($inputName, $inputValues)) {
                    throw new BadRequestHttpException("Missing required input: $inputName");
                }
                /** @var EntityInterface $value */
                $body = $inputValues[$inputName];
                $expectedInputGroup = $inputConfig['group'];

                $errors = $this->validateBody($body, $expectedInputType, $expectedInputGroup, $version);
            } else {
                if (array_key_exists($inputName, $inputValues)) {
                    $value = $inputValues[$inputName];
                    $errors = $this->checkParam($inputName, $value, $inputConfig['type'], $inputConfig['format']);
                } elseif ($mode == Configuration::MODE_REQUIREMENT) {
                    throw new BadRequestHttpException("Missing required input: $inputName");
                }
            }

            if (count($errors)) {
                $this->throwInputValidationErrors($errors);
            }
        }
    }

    protected function checkParam($name, $param, $type, $format)
    {
        $validator = $this->getValidator();

        $constraints = array();

        if ($type == Configuration::DATETIME) {
            $constraints[] = new DateTime(
                array(
                    'message' => sprintf(
                        "Parameter '%s' with value '%s', doesn't have a valid date format",
                        $name,
                        $param
                    ),
                )
            );
        } else {
            $constraints[] = new Type(
                array(
                    'type' => $type,
                    'message' => sprintf(
                        "Parameter '%s' with value '%s', is not of type '%s'",
                        $name,
                        $param,
                        $type
                    ),
                )
            );
        }

        if ($format) {
            $constraints[] = new Regex(
                array(
                    'pattern' => '#^'.$format.'$#xsu',
                    'message' => sprintf(
                        "Parameter '%s' with value '%s', does not match format '%s'",
                        $name,
                        $param,
                        $format
                    ),
                )
            );
        }

        $errors = new ConstraintViolationList();

        foreach ($constraints as $constraint) {
            $errors->addAll($validator->validate($param, $constraint));
        }


        return $errors;
    }

    protected function validateBody($body, $expectedType, $group, $version)
    {
        $validator = $this->getValidator();

        $shouldBeArray = strpos($expectedType, ApiConfigurator::$arraySymbol) !== false;
        $elementType = str_replace(ApiConfigurator::$arraySymbol, "", $expectedType);

        if (is_array($body) && !$shouldBeArray) {
            throw new \Exception("The output is an array but an object was expected");
        } elseif (!is_array($body) && $shouldBeArray) {
            throw new \Exception("The output was expected to be an array but it isn't");
        }

        if ($shouldBeArray) {
            foreach ($body as $elementKey => $elementValue) {
                if (!($elementValue instanceof $elementType) || !($elementValue instanceof EntityInterface)) {
                    throw new \Exception("The output is not an instance of the expected class");
                }
            }
        } else {
            if (!($body instanceof $elementType) || !($body instanceof EntityInterface)) {
                throw new \Exception("The output is not an instance of the expected class");
            }
        }

        $errors = $validator->validate($body);

        return $errors;
    }

    protected function validateOutput($outputValue)
    {
        $configurator = $this->get('smartapi.configurator');
        $request = $this->getRequest();
        $serviceId = $request->get('serviceId');
        $methodName = $request->get('methodName');
        $version = $request->get('version');

        $methodConfig = $configurator->getConfig($serviceId, $methodName);

        if ($outputValue && !array_key_exists('output', $methodConfig)) {
            throw new \Exception("This API method should return an empty response");
        } else {
            if (!$outputValue && array_key_exists('output', $methodConfig)) {
                throw new \Exception("This API method should return a response");
            }
        }

        if ($outputValue) {
            $outputConfig = $methodConfig['output'];
            $outputType = $outputConfig['type'];
            $outputGroup = $outputConfig['group'];

            $errors = $this->validateBody($outputValue, $outputType, $outputGroup, $version);

            if (count($errors)) {
                $this->throwOutputValidationErrors($errors);
            }
        }
    }

    protected function resolveHeaders($data)
    {
        // TODO: GENERALIZE
        $locationResolver = $this->get('smartapi.resolvers.location');

        $toResolve = $data;
        if (!is_array($data) || $data instanceof \Traversable) {
            $toResolve = array($data);
        }

        foreach ($toResolve as $header) {
            if ($header instanceof Location) {
                $locationResolver->resolve($header);
            }
        }
    }

    /**
     * @param $data
     * @return null|BasicResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    protected function respond($data)
    {
        if($data instanceof OK){
            $data = null;
        }

        $response = null;
        $request = $this->getRequest();
        $config = $request->get('methodConfig');
        $outputMode = @$config['output']['mode'];
        $successCode = $config['successCode'];
        $outputGroup = @$config['output']['group'];

        if ($outputMode == Configuration::MODE_HEADER) {
            $this->resolveHeaders($data);
        }

        $this->validateOutput($data);

        // REST
        if ($request->get('api') == 'rest') {
            $headers = array();

            // REST HEADERS
            if ($outputMode == Configuration::MODE_HEADER) {
                if ($data instanceof HeaderInterface) {
                    $headers = array($data->getHeaderName() => $data->getRESTHeaderValue());
                } elseif (is_array($data) || $data instanceof \Traversable) {
                    $headers = array();
                    /** @var HeaderInterface $header */
                    foreach ($data as $header) {
                        $headers[$header->getHeaderName()] = $header->getRESTHeaderValue();
                    }
                }

                $data = null;
            }

            $view = $this->view($data, $successCode, $headers);

            $context = SerializationContext::create()->setVersion($request->get('version'));
            $context->setGroups(array($outputGroup));

            $view->setSerializationContext($context);

            $response = $this->handleView($view);
        } else {    // SOAP
            if (!$data) {
                $desc = $this->get('smartapi.configurator')->getSuccessCodeDescription($successCode);
                $data = new BasicResponse($successCode, $desc);
            }

            $response = $data;
        }

        return $response;
    }

    public function handleCallAction($serviceId, $serviceName, $version, $methodConfig, $methodName, $input)
    {
        $this->checkAuthorization();

        $inputsConfig = $methodConfig['input'];
        $this->prepareInput($version, $inputsConfig, $input);
        $this->checkInput($version, $inputsConfig, $input);

        return $this->respond("Please place here your response");
    }
}