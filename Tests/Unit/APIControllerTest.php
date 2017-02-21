<?php

namespace Smartbox\ApiBundle\Tests\Unit;

use Smartbox\ApiBundle\Tests\BaseKernelTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class APIControllerTest extends BaseKernelTestCase
{

    /**
     * Check input test data provider
     *
     * @return array
     */
    public function methodConfigProvider()
    {
        return [
            'Case when the input is empty' => [
                'version' => 'v1',
                'inputsConfig' => [
                    'voucherDiscounts' => [
                        'limitElements' => '100',
                       'type' => 'ActivationVoucherEvent[]',
                        'group' => 'group',
                        'mode' => 'body'
                    ]
                ],
                'inputValues' => ['voucherDiscounts' => []],
                'exceptionClass' => BadRequestHttpException::class
            ]
        ];
    }

    /**
     * @dataProvider methodConfigProvider
     *
     * @param $version
     * @param $inputsConfig
     * @param $inputValues
     * @param $exceptionClass
     */
    public function testCheckInput($version, $inputsConfig, $inputValues, $exceptionClass)
    {
        $this->expectException($exceptionClass);

        $controller = $this->getContainer()->get('test.dummy.controller');

        $controller->checkInput($version, $inputsConfig, $inputValues);
    }

}