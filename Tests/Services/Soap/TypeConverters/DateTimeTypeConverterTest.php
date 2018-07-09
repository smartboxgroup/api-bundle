<?php

namespace Smartbox\ApiBundle\Tests\Services\Soap\TypeConverters;

use Smartbox\ApiBundle\Services\Soap\TypeConverters\DateTimeTypeConverter;

class DateTimeTypeConverterTest extends \BeSimple\SoapCommon\Tests\Converter\DateTimeTypeConverterTest
{
    /**
     * @param $invalidDate
     *
     * @dataProvider dataProviderForInvalidDate
     * @expectedException \BeSimple\SoapServer\Exception\SenderSoapFault
     *
     * @throws SenderSoapFault
     */
    public function testConvertXmlToPhpForInvalidDate($invalidDate)
    {
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
