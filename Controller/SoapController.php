<?php

namespace Smartbox\ApiBundle\Controller;


use BeSimple\SoapBundle\Controller\SoapWebServiceController;
use BeSimple\SoapBundle\Soap\SoapRequest;
use BeSimple\SoapServer\SoapServer;
use Smartbox\ApiBundle\Services\Soap\WebServiceContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SoapController extends SoapWebServiceController
{

    /**
     * @param $webservice
     * @return \BeSimple\SoapBundle\Soap\SoapResponse
     * @throws \Exception
     */
    public function callAction($webservice)
    {
        $webServiceContext = $this->getWebServiceContext($webservice);

        $soapRequest = SoapRequest::createFromHttpRequest($this->container->get('request'));

        if ($soapRequest == null) {
            throw new \Exception("Soap request is empty");
        }

        $this->soapRequest = $soapRequest;
        $this->serviceBinder = $webServiceContext->getServiceBinder();

        $this->soapServer = $webServiceContext
            ->getServerBuilder()
            ->withSoapVersion11()
            ->withHandler($this)
            ->build();

        if ($this->soapServer instanceof SoapServer) {
            $filter = $this->container->get('smartapi.soap.security.authentication_filter');
            $this->soapServer->getSoapKernel()->registerFilter($filter);
        } else {
            throw new \Exception('SoapServer in SoapController expected to be of class BeSimple\SoapServer\SoapServer');
        }

        ob_start();
        $this->soapServer->handle($this->soapRequest->getSoapMessage());

        $response = $this->getResponse();
        $response->setContent(ob_get_clean());

        return $response;
    }

    /**
     * @param $webservice
     * @return WebServiceContext
     */
    private function getWebServiceContext($webservice)
    {
        $context = sprintf('besimple.soap.context.%s', $webservice);

        if (!$this->container->has($context)) {
            throw new NotFoundHttpException(sprintf('No WebService with name "%s" found.', $webservice));
        }

        return $this->container->get($context);
    }
}