<?php

namespace Smartbox\ApiBundle\Tests\Controller;

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
                'exceptionClass' => BadRequestHttpException::class,
            ]
        ];
    }

    /**
     * @dataProvider methodConfigProvider
     *
     * @param string $version
     * @param array $inputsConfig
     * @param array $inputValues
     * @param string $exceptionClass
     */
    public function testCheckInput($version, array $inputsConfig, array $inputValues, $exceptionClass)
    {
        $this->expectException($exceptionClass);

        $controller = $this->getContainer()->get('test.dummy.controller');

        $controller->checkInput($version, $inputsConfig, $inputValues);
    }
}
