<?php

namespace Smartbox\ApiBundle\Services\Soap\TypeConverters;

use BeSimple\SoapServer\Exception\SenderSoapFault;

class DateTimeTypeConverter extends \BeSimple\SoapCommon\Converter\DateTimeTypeConverter
{
    /**
     * {@inheritdoc}
     */
    public function convertXmlToPhp($data)
    {
        try {
            return parent::convertXmlToPhp($data);
        } catch (\Exception $ex) {
            $doc = new \DOMDocument();
            $doc->loadXML($data);
            $content = $doc->textContent;

            throw new SenderSoapFault(
                sprintf(
                    'SOAP-ERROR: Encoding: Violation of encoding rules in date property, found "%s", hence a valid datetime was expected',
                    $content
                )
            );
        }
    }
}

