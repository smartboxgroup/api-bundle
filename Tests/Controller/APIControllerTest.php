<?php

namespace Smartbox\ApiBundle\Tests\Controller;

use Smartbox\ApiBundle\Services\ApiConfigurator;
use Smartbox\ApiBundle\Tests\Fixtures\Entity\Box;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class APIControllerTest extends WebTestCase
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
        if (null !== $exceptionClass) {
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
        $response = $controller->handleCallAction('demo_v1', 'dummy', $methodConfig, $version, $methodName, $inputValues);

        $this->assertIsArray($response);
        $this->assertInstanceOf(Box::class, $response[0]);
    }

    /**
     * @dataProvider provideQuery
     */
    public function testFilters(int $expected, string $query, string $message = null)
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $client->request('GET', "/api/rest/sdk/v0/test3/filters?$query");
        $res = $client->getResponse();

        $this->assertSame($expected, $res->getStatusCode(), "Expected HTTP $expected, got:\n{$res}");

        if ($message) {
            $json = json_decode($res->getContent(), true);

            $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'Invalid JSON returned');
            $this->assertArrayHasKey('message', $json, 'Payload should contain "message" key.');
            $this->assertContains($message, $json['message']);
        }
    }

    private function getContainer()
    {
        if (!static::$kernel || !($container = static::$kernel->getContainer())) {
            static::bootKernel();

            $container = static::$kernel->getContainer();
        }

        return $container;
    }

    public function provideQuery()
    {
        $query = [
            'listOfNumber' => [1, 2, 3, 4.5],
            'listOfString' => ['foo', 'bar', 'baz'],
        ];

        yield 'Valid query' => [204, http_build_query($query)];
        yield 'Valid number' => [204, http_build_query(['size' => 1.5])];

        $query['listOfNumber'][] = 'Definitely not a number';

        yield 'Invalid type' => [
            400,
            http_build_query($query),
            'Parameter \'listOfNumber\' with value "Definitely not a number" is not a valid',
        ];

        unset($query['listOfNumber']);
        $query['listOfString'][] = 'Wesh alors';

        yield 'Invalid format' => [
            400,
            http_build_query($query),
            'Parameter \'listOfString\' with value "Wesh alors", does not match format \'foo|bar|baz\'',
        ];

        yield 'Invalid number' => [400, http_build_query(['size' => '....15'])];
    }
}
