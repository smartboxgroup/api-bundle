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
     * HACK: Overrides the default handle method to offer a better soap fault creation that also contains
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
            $this->fault($fault->faultcode, $fault->faultstring, null, $fault->detail);
        }
    }
}
