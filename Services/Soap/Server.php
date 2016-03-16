<?php

namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapServer\SoapRequest;
use BeSimple\SoapServer\SoapServer;

/**
 * Class Server
 *
 * @package \Smartbox\ApiBundle\Soap
 */
class Server extends SoapServer
{
    /**
     * Overrides the default handle method to offer a better soap fault creation that also contains
     * actor and details
     *
     * @param null $request
     */
    public function handle($request = null)
    {
        // wrap request data in SoapRequest object
        $soapRequest = SoapRequest::create($request, $this->soapVersion);

        // handle actual SOAP request
        try {
            $soapResponse = $this->handle2($soapRequest);
            // send SOAP response to client
            $soapResponse->send();
        } catch (\SoapFault $fault) {
            // issue an error to the client
            $this->raiseFaultForException($fault);
        }
    }

    /**
     * Holds the knowledge about how to generate a fault
     * @param \SoapFault $fault
     */
    protected function raiseFaultForException(\SoapFault $fault)
    {
        $this->fault($fault->faultcode, $fault->faultstring, null, $fault->detail);
    }
}
