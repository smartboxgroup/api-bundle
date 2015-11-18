<?php

namespace Smartbox\ApiBundle\Services\Rest;

use FOS\RestBundle\Request\RequestBodyParamConverter;
use JMS\Serializer\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter as ParamConverterConfiguration;
use Smartbox\ApiBundle\DependencyInjection\Configuration;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class RestInputHandler
 *
 * Based on FOS\RestBundle\Request\AbstractRequestBodyParamConverter
 *
 * @package Smartbox\ApiBundle\Services\Rest
 */
class RestInputHandler
{

    /** @var  ApiConfigurator */
    protected $apiConfigurator;

    /** @var  RequestBodyParamConverter */
    protected $paramConverter;

    /** @var  Serializer */
    protected $serializer;

    protected $context = array();

    /** @var  ValidatorInterface */
    protected $validator;

    function __construct(
        ApiConfigurator $apiConfigurator,
        RequestBodyParamConverter $paramConverter,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        $this->apiConfigurator = $apiConfigurator;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->paramConverter = $paramConverter;
    }

    /**
     * @return ApiConfigurator
     */
    public function getApiConfigurator()
    {
        return $this->apiConfigurator;
    }


    /**
     * @param GetResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $serviceId = $request->get('serviceId');
        $version = $request->get('version');
        $methodName = $request->get('methodName');
        $api = $request->get('api');

        if (!$serviceId || !$methodName || $api != 'rest') {
            return;
        }

        $config = $this->getApiConfigurator()->getConfig($serviceId, $methodName);
        $inputParamsConfig = $config['input'];

        // Gather all input parameters
        $bag = array();

        foreach ($inputParamsConfig as $inputName => $inputConfig) {
            $mode = $inputConfig['mode'];
            $type = $inputConfig['type'];

            if ($mode == Configuration::MODE_BODY) {
                $this->convertBody($request, $inputName, $inputConfig, $version);
            }

            $param = $request->get($inputName);

            if (empty($param) && $mode != Configuration::MODE_FILTER) {
                throw new BadRequestHttpException(
                    "Missing required input parameter: $inputName"
                );
            }

            if (!empty($param)) {
                $bag[$inputName] = $this->getApiConfigurator()->getCleanParameter($inputName, $type, $param);
            }
        }

        $request->attributes->set('input', $bag);
    }

    public function convertBody(Request $request, $name, $config, $version)
    {
        // Group support
        $group = $config['group'];

        $options = array(
            "deserializationContext" => array(
                'version' => $version
            )
        );

        $options['deserializationContext']['groups'] = array($group);

        $configuration = new ParamConverterConfiguration(
            array(
                'name' => $name,
                'class' => ApiConfigurator::getJMSType($config['type']),
                'options' => $options,
                'converter' => 'fos_rest.request_body'
            )
        );

        // Convert
        $this->paramConverter->apply($request, $configuration);
    }


    /**
     * Check if we should try to decode the body
     *
     * @param Request $request
     * @return bool
     */
    protected function isDecodeable(Request $request)
    {
        if (!in_array($request->getMethod(), array('POST', 'PUT', 'PATCH', 'DELETE'))) {
            return false;
        }

        return !$this->isFormRequest($request);
    }

    /**
     * Check if the content type indicates a form submission
     *
     * @param Request $request
     * @return bool
     */
    protected function isFormRequest(Request $request)
    {
        $contentTypeParts = explode(';', $request->headers->get('Content-Type'));

        if (isset($contentTypeParts[0])) {
            return in_array($contentTypeParts[0], array('multipart/form-data', 'application/x-www-form-urlencoded'));
        }

        return false;
    }
}