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
 * Class RestInputHandler.
 *
 * Based on FOS\RestBundle\Request\AbstractRequestBodyParamConverter
 */
class RestInputHandler
{
    /** @var ApiConfigurator */
    protected $apiConfigurator;

    /** @var RequestBodyParamConverter */
    protected $paramConverter;

    /** @var Serializer */
    protected $serializer;

    protected $context = [];

    /** @var ValidatorInterface */
    protected $validator;

    public function __construct(
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
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $serviceId = $request->get(ApiConfigurator::SERVICE_ID);
        $version = $request->get(ApiConfigurator::VERSION);
        $methodName = $request->get(ApiConfigurator::METHOD_NAME);
        $api = $request->get('api');

        if (!$serviceId || !$methodName || 'rest' != $api) {
            return;
        }

        $config = $this->getApiConfigurator()->getConfig($serviceId, $methodName);
        $inputParamsConfig = $config[ApiConfigurator::INPUT];

        // Gather all input parameters
        $bag = [];

        foreach ($inputParamsConfig as $inputName => $inputConfig) {
            $mode = $inputConfig['mode'];
            $type = $inputConfig['type'];

            if (Configuration::MODE_BODY == $mode) {
                $this->convertBody($request, $inputName, $inputConfig, $version);
            }

            $param = $request->get($inputName);

            if (empty($param) && Configuration::MODE_FILTER != $mode) {
                throw new BadRequestHttpException("Missing required input parameter: $inputName");
            }

            if (!empty($param)) {
                $bag[$inputName] = $this->getApiConfigurator()->getCleanParameter($inputName, $type, $param);
            }
        }

        $request->attributes->set(ApiConfigurator::INPUT, $bag);
    }

    public function convertBody(Request $request, $name, $config, $version)
    {
        // Group support
        $group = $config['group'];

        $options = [
            'deserializationContext' => [
                'version' => $version,
            ],
        ];

        $options['deserializationContext']['groups'] = [$group];

        $configuration = new ParamConverterConfiguration(
            [
                'name' => $name,
                'class' => ApiConfigurator::getJMSType($config['type']),
                'options' => $options,
                'converter' => 'fos_rest.request_body',
            ]
        );

        // Convert
        $this->paramConverter->apply($request, $configuration);
    }

    /**
     * Check if we should try to decode the body.
     *
     * @return bool
     */
    protected function isDecodeable(Request $request)
    {
        if (!in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }

        return !$this->isFormRequest($request);
    }

    /**
     * Check if the content type indicates a form submission.
     *
     * @return bool
     */
    protected function isFormRequest(Request $request)
    {
        $contentTypeParts = explode(';', $request->headers->get('Content-Type'));

        if (isset($contentTypeParts[0])) {
            return in_array($contentTypeParts[0], ['multipart/form-data', 'application/x-www-form-urlencoded']);
        }

        return false;
    }
}
