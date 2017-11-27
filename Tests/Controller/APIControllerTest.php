<?php

namespace Smartbox\ApiBundle\Tests\Controller;

use Smartbox\ApiBundle\Services\ApiConfigurator;
use Smartbox\ApiBundle\Tests\BaseKernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class APIControllerTest extends BaseKernelTestCase
{
    /**
     * Check input test data provider.
     *
     * @return array
     */
    public function methodHandleCallActionProvider()
    {
        return [
            'Case when the input is empty and we expect an array' => [
                'version' => 'v1',
                'methodName' => 'createBoxes',
                'methodConfig' => [
                    ApiConfigurator::INPUT => [
                        'boxes' => [
                            'limitElements' => 100,
                            'type' => 'Box[]',
                            'group' => 'group',
                            'mode' => 'body',
                        ],
                    ],
                ],
                'inputValues' => ['boxes' => []],
                'exceptionClass' => BadRequestHttpException::class,
            ],
            'Case when the output contains more elements thant expected' => [
                'version' => 'v1',
                'methodName' => 'getBoxes',
                'methodConfig' => [
                    ApiConfigurator::INPUT => [
                        'quantity' => [
                            'limitElements' => null,
                            'type' => 'int',
                            'group' => null,
                            'mode' => 'requirement',
                            'format' => null,
                        ],
                    ],
                ],
                'inputValues' => ['quantity' => 3],
                'exceptionClass' => HttpException::class,
            ],
            'Happy case' => [
                'version' => 'v1',
                'methodName' => 'getBoxes',
                'methodConfig' => [
                    ApiConfigurator::INPUT => [
                        'quantity' => [
                            'limitElements' => null,
                            'type' => 'int',
                            'group' => null,
                            'mode' => 'requirement',
                            'format' => null,
                        ],
                    ],
                ],
                'inputValues' => ['quantity' => 1],
                'exceptionClass' => null,
            ],
        ];
    }

    /**
     * @dataProvider methodHandleCallActionProvider
     *
     * @param $methodConfig
     * @param $version
     * @param $methodName
     * @param array $inputValues
     * @param $exceptionClass
     */
    public function testHandleCallAction($version, $methodName, $methodConfig, array $inputValues, $exceptionClass)
    {
        if (!is_null($exceptionClass)) {
            $this->expectException($exceptionClass);
        }

        // Prepare the request
        $requestStack = $this->getContainer()->get('request_stack');
        $request = new Request(
            [],
            [],
            [
                ApiConfigurator::METHOD_NAME => $methodName,
                ApiConfigurator::SERVICE_ID => 'demo_v1',
                ApiConfigurator::VERSION => $version,
            ]
        );
        $requestStack->push($request);

        // Set the authentication
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $token = new AnonymousToken('secret', 'test', ['ROLE_USER']);
        $tokenStorage->setToken($token);

        // Call handleCallAction
        $controller = $this->getContainer()->get('test.dummy.controller');
        $respond = $controller->handleCallAction('demo_v1', 'dummy', $methodConfig, $version, $methodName, $inputValues);
    }
}
