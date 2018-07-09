<?php

namespace Smartbox\ApiBundle\Tests\Services\Soap\TypeConverters;

use BeSimple\SoapServer\Exception\SenderSoapFault;
use Smartbox\ApiBundle\Services\Soap\TypeConverters\DateTimeTypeConverter;

class DateTimeTypeConverterTest extends \BeSimple\SoapCommon\Tests\Converter\DateTimeTypeConverterTest
{
    /**
     * @dataProvider dataProviderForInvalidDate
     *
     * @param $invalidDate
     *
     * @throws SenderSoapFault
     */
    public function testConvertXmlToPhpForInvalidDate($invalidDate)
    {
        $this->setExpectedException(SenderSoapFault::class);
        $converter = new DateTimeTypeConverter();

        $dateXml = '<sometag>'.$invalidDate.'</sometag>';
        $converter->convertXmlToPhp($dateXml);
    }

    public function dataProviderForInvalidDate()
    {
        return [
            ['this is invalid date'],
        ];
    }
}
