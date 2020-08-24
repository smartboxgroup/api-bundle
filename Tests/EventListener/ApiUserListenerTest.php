<?php

namespace Smartbox\ApiBundle\Tests\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @group user-provider
 */
class ApiUserListenerTest extends WebTestCase
{
    /**
     * @param int $expected
     *
     * @dataProvider provideEndpoints
     */
    public function testOnKernelRequest(array $server, $expected = 204)
    {
        $client = static::createClient([], $server);

        $client->request('PUT', '/api/rest/demo/v1/box/42/picked');
        $res = $client->getResponse();

        $this->assertSame(
            $expected,
            $res->getStatusCode(),
            "Expected HTTP $expected response, got {$res->getStatusCode()}: {$res->getContent()}."
        );

        if ($res->isClientError()) {
            $this->assertSame(
                '{"code":403,"message":"You are not allowed to use this flow."}',
                $res->getContent()
            );
        }
    }

    /**
     * @return \Generator
     */
    public function provideEndpoints()
    {
        $server = [
            'PHP_AUTH_USER' => 'regular',
            'PHP_AUTH_PW' => 'P4$$W0rd',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        yield 'Access denied' => [$server, 403];

        $server['PHP_AUTH_USER'] = 'box_picker';
        yield 'User with correct flow' => [$server];

        $server['PHP_AUTH_USER'] = 'admin';
        $server['PHP_AUTH_PW'] = 'admin';
        yield 'Admin' => [$server];
    }
}
