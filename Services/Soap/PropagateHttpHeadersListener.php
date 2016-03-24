<?php

namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapBundle\Soap\SoapHeader;
use BeSimple\SoapBundle\Soap\SoapResponse;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class ExposedHeadersListener
 *
 * @package \Smartbox\ApiBundle\Services\Soap
 */
class PropagateHttpHeadersListener
{
    /**
     * @var ApiConfigurator
     */
    protected $apiConfigurator;

    /**
     * ExposedHeadersListener constructor.
     *
     * @param ApiConfigurator $apiConfigurator
     */
    public function __construct(ApiConfigurator $apiConfigurator)
    {
        $this->apiConfigurator = $apiConfigurator;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     *
     * @throws \Exception
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if($event->getResponse() instanceof SoapResponse){
            /** @var SoapResponse $response */
            $response = $event->getResponse();
            $request = $event->getRequest();
            $serviceId = $request->get(ApiConfigurator::SERVICE_ID);

            if (null !== $serviceId) {
                $config = $this->apiConfigurator->getConfig($serviceId);
                $soapHeaderNamespace = $config['soapHeadersNamespace'];
                if ($serviceId && isset($config['propagateHttpHeadersToSoap']) && !empty($config['propagateHttpHeadersToSoap'])) {
                    foreach($config['propagateHttpHeadersToSoap'] as $headerName => $headerAlias) {
                        if ($response->headers->has($headerName) && !$response->getSoapHeaders()->has($headerAlias)) {
                            $headerValue = $response->headers->get($headerName);
                            $response->addSoapHeader(new SoapHeader($soapHeaderNamespace, $headerAlias, $headerValue));
                        }
                    }
                }
            }
        }
    }
}
