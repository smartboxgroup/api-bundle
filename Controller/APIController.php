<?php

namespace Smartbox\ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use JMS\Serializer\SerializationContext;
use Smartbox\ApiBundle\DependencyInjection\Configuration;
use Smartbox\ApiBundle\Entity\BasicResponse;
use Smartbox\ApiBundle\Entity\OK;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Smartbox\CoreBundle\Type\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolation;
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
            $message = 'Bad Request; ';
            /** @var ConstraintViolationInterface $error */
            foreach ($list as $error) {
                $message .= $error->getPropertyPath().':'.$error->getMessage().',';
            }

            throw new BadRequestHttpException($message);
        }
    }

    protected function throwOutputValidationErrors(ConstraintViolationListInterface $list)
    {
        if (count($list) > 0) {
            $message = 'External system failure; ';
            /** @var ConstraintViolationInterface $error */
            foreach ($list as $error) {
                $message .= $error->getPropertyPath().':'.$error->getMessage().',';
            }

            throw new HttpException(520, $message);
        }
    }

    protected function checkAuthorization()
    {
        $configurator = $this->get('smartapi.configurator');
        $request = $this->getRequest();
        $serviceId = $request->get(ApiConfigurator::SERVICE_ID);
        $methodName = $request->get(ApiConfigurator::METHOD_NAME);
        $config = $configurator->getConfig($serviceId, $methodName);
        $roles = $config['roles'];

        if (false === $this->get('security.authorization_checker')->isGranted($roles)) {
            throw new  AccessDeniedHttpException('Access denied');
        }
    }

    protected function prepareInput($version, $inputsConfig, $inputValues)
    {
        foreach ($inputsConfig as $inputName => $inputConfig) {
            if (Configuration::MODE_BODY == $inputConfig['mode']) {
                if (!array_key_exists($inputName, $inputValues)) {
                    throw new BadRequestHttpException("Missing required input: $inputName");
                }
                $body = $inputValues[$inputName];
                $group = $inputConfig['group'];
                $this->getGroupVersionHydrator()->hydrate($body, $group, $version);
            }
        }
    }

    protected function checkInput($version, $inputsConfig, $inputValues)
    {
        foreach ($inputsConfig as $inputName => $inputConfig) {
            $mode = $inputConfig['mode'];
            $expectedInputType = $inputConfig['type'];
            $expectedLimitElements = $inputConfig['limitElements'];

            $errors = array();

            if (Configuration::MODE_BODY == $mode) {
                if (!array_key_exists($inputName, $inputValues)) {
                    throw new BadRequestHttpException("Missing required input: $inputName");
                }
                /** @var EntityInterface $value */
                $body = $inputValues[$inputName];
                $expectedInputGroup = $inputConfig['group'];

                $shouldBeArray = false !== strpos($expectedInputType, ApiConfigurator::$arraySymbol);

                if ($shouldBeArray && is_array($body) && empty($body)) {
                    throw new BadRequestHttpException('The input should not be an empty array');
                }

                try {
                    $errors = $this->validateBody($body, $expectedInputType, $expectedInputGroup, $expectedLimitElements, $version);
                } catch (\Exception $e) {
                    $errors = new ConstraintViolationList(array(
                        new ConstraintViolation($e->getMessage(), '', array(), 'body', 'body', $body),
                    ));
                }
            } else {
                if (array_key_exists($inputName, $inputValues)) {
                    $value = $inputValues[$inputName];
                    $errors = $this->checkParam($inputName, $value, $inputConfig['type'], $inputConfig['format']);
                } elseif (Configuration::MODE_REQUIREMENT == $mode) {
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

        switch ($type) {
            case Configuration::DATETIME:
                $constraints[] = new DateTime(
                    array(
                        'message' => sprintf(
                            "Parameter '%s' with value '%s', doesn't have a valid date format",
                            $name,
                            $param->format('c')
                        ),
                    )
                );
            break;
            case Configuration::BOOL:
                // do some thing here
                $constraints[] = new Type(
                    array(
                        'message' => sprintf(
                            "Parameter '%s' with value '%s', is not a valid bool.",
                            $name,
                            $param
                        ),
                        'type' => 'bool',
                    )
                );
                break;
            default:
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
        }

        $errors = new ConstraintViolationList();

        foreach ($constraints as $constraint) {
            $errors->addAll($validator->validate($param, $constraint));
        }

        return $errors;
    }

    /**
     * Method to get a specific REST Header or SOAP Header given the header name.
     *
     * @param Request $request    request that can be HTTP or SOAP
     * @param string  $headerName header name to get its value
     *
     * @return string
     */
    protected function getHeader(Request $request, $headerName)
    {
        // SOAP
        if ('soap' == $request->get('api')) {
            $soapHeader = $request->getSoapHeaders()->get($headerName);

            if (null !== $soapHeader) {
                return $soapHeader->getData();
            }
        }

        // REST
        return $request->headers->get($headerName);
    }

    /**
     * Get and validate the required headers for a specific method.
     *
     * @param array $headers  header names
     * @param bool  $required
     *
     * @return array
     */
    protected function validateHeaders(array $headers, $required = true)
    {
        $request = $this->getRequest();

        $existingHeaders = [];

        foreach ($headers as $headerName) {
            $headerValue = $this->getHeader($request, $headerName);

            if (null !== $headerValue) {
                $existingHeaders[$headerName] = $headerValue;
            }

            if (null === $headerValue && $required) {
                throw new BadRequestHttpException(sprintf('"%s" header is required to use this method', $headerName));
            }
        }

        return $existingHeaders;
    }

    protected function validateBody($body, $expectedType, $group, $expectedLimitElements, $version)
    {
        $validator = $this->getValidator();

        $shouldBeArray = false !== strpos($expectedType, ApiConfigurator::$arraySymbol);
        $elementType = str_replace(ApiConfigurator::$arraySymbol, '', $expectedType);

        if (is_array($body) && !$shouldBeArray) {
            throw new \Exception('The body is an array but an object was expected');
        } elseif (!is_array($body) && $shouldBeArray) {
            throw new \Exception("The body was expected to be an array but it isn't");
        }

        if ($shouldBeArray) {
            if (null !== $expectedLimitElements && count($body) > $expectedLimitElements) {
                throw new \Exception('The body contains more elements than expected');
            }
            foreach ($body as $elementKey => $elementValue) {
                if (!($elementValue instanceof $elementType) || !($elementValue instanceof EntityInterface)) {
                    throw new \Exception('The body is not an instance of the expected class');
                }
            }
        } else {
            if (!($body instanceof $elementType) || !($body instanceof EntityInterface)) {
                throw new \Exception('The body is not an instance of the expected class');
            }
        }

        $errors = $validator->validate($body);

        return $errors;
    }

    protected function validateOutput($outputValue)
    {
        $apiConfigurator = $this->get('smartapi.configurator');
        $request = $this->getRequest();
        $serviceId = $request->get(ApiConfigurator::SERVICE_ID);
        $methodName = $request->get(ApiConfigurator::METHOD_NAME);
        $version = $request->get(ApiConfigurator::VERSION);

        $methodConfig = $apiConfigurator->getConfig($serviceId, $methodName);

        if ($outputValue && !array_key_exists('output', $methodConfig)) {
            throw new \Exception('This API method should return an empty response');
        } elseif (!is_array($outputValue) && empty($outputValue) && array_key_exists('output', $methodConfig)) {
            throw new \Exception('This API method should return a response');
        }

        if ($outputValue) {
            $outputConfig = $methodConfig['output'];
            $outputType = $outputConfig['type'];
            $outputGroup = $outputConfig['group'];
            $expectedLimitElements = $outputConfig['limitElements'];

            $this->getGroupVersionHydrator()->hydrate($outputValue, $outputGroup, $version);
            try {
                $errors = $this->validateBody($outputValue, $outputType, $outputGroup, $expectedLimitElements, $version);
            } catch (\Exception $e) {
                $errors = new ConstraintViolationList(array(
                    new ConstraintViolation($e->getMessage(), '', array(), 'body', 'body', $outputValue),
                ));
            }

            if (count($errors)) {
                $this->throwOutputValidationErrors($errors);
            }
        }
    }

    /**
     * @param $body
     *
     * @return null|BasicResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    protected function respond($body, $headers = [])
    {
        if ($body instanceof OK) {
            $body = null;
        }

        $response = null;
        $apiConfigurator = $this->get('smartapi.configurator');
        $request = $this->getRequest();
        $serviceId = $request->get(ApiConfigurator::SERVICE_ID);
        $methodName = $request->get(ApiConfigurator::METHOD_NAME);

        $methodConfig = $apiConfigurator->getConfig($serviceId, $methodName);

        $successCode = $methodConfig['successCode'];
        $outputGroup = (isset($methodConfig['output']['group'])) ? $methodConfig['output']['group'] : null;

        $this->validateOutput($body);

        // REST
        if ('rest' == $request->get('api')) {
            // REST HEADERS
            if (in_array($successCode, $apiConfigurator->getRestEmptyBodyResponseCodes())) {
                $body = null;
            }

            $view = $this->view($body, $successCode, $headers);

            $context = SerializationContext::create()->setVersion($request->get('version'));
            $context->setGroups(array($outputGroup));

            $view->setSerializationContext($context);

            /** @var Response $response */
            $response = $this->handleView($view);
        } else {    // SOAP
            if (!$body) {
                $desc = $apiConfigurator->getSuccessCodeDescription($successCode);
                $body = new BasicResponse($successCode, $desc);
            }

            $response = $body;
            $this->get('besimple.soap.response')->headers->add($headers);
        }

        return $response;
    }

    public function handleCallAction($serviceId, $serviceName, $version, $methodConfig, $methodName, $input)
    {
        $this->checkAuthorization();

        $inputsConfig = $methodConfig[ApiConfigurator::INPUT];
        $this->prepareInput($version, $inputsConfig, $input);
        $this->checkInput($version, $inputsConfig, $input);

        return $this->respond('Please place here your response');
    }
}
