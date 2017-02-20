<?php

namespace Smartbox\ApiBundle\Tests\Unit;

use Smartbox\ApiBundle\Tests\BaseKernelTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class APIControllerTest extends BaseKernelTestCase
{

    public function methodConfigWithEmptyInputProvider()
    {
        return [[
                'version' => 'v1',
                'inputsConfig' => [
                    'voucherDiscounts' => [
                        'limitElements' => '100',
                       'type' => 'ActivationVoucherEvent[]',
                        'group' => 'group',
                        'mode' => 'body'
                    ]
                ],
                'inputValues' => ['voucherDiscounts' => []]
            ]
        ];
    }

    /**
     * @dataProvider methodConfigWithEmptyInputProvider
     * @param $version
     * @param $inputsConfig
     * @param $inputValues
     */
    public function testCheckInput($version, $inputsConfig, $inputValues)
    {
        $this->expectException(BadRequestHttpException::class);

        $controller = $this->getContainer()->get('test.dummy.controller');

        $controller->checkInput($version, $inputsConfig, $inputValues);
    }

}