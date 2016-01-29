<?php

namespace Smartbox\ApiBundle\Services\Soap\TypeConverters;

use BeSimple\SoapServer\Exception\SenderSoapFault;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Christian Kerl <christian-kerl@web.de>
 */
class DateTimeTypeConverter extends \BeSimple\SoapCommon\Converter\DateTimeTypeConverter
{
    public function convertXmlToPhp($data)
    {
        try{
            parent::convertXmlToPhp($data);
        }catch (\Exception $ex){
            $doc = new \DOMDocument();
            $doc->loadXML($data);
            $content = $doc->textContent;

            $message = 'SOAP-ERROR: Encoding: Violation of encoding rules in date property, found "%s", hence a datetime with format "%s" was expected';

            throw new SenderSoapFault(sprintf($message,$content,'Y-m-d\TH:i:sP'));
        }
    }
}

