<?php

namespace Smartbox\ApiBundle\Tests\EventListener;

use FOS\RestBundle\Util\Codes;
use phpDocumentor\Reflection\DocBlock;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Client;

class ThrottlingListenerTest extends WebTestCase
{
    /** @var  Client */
    protected $client;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setUp()
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $this->application = new Application($kernel);
        $this->container = $kernel->getContainer();
    }

    public function getConfig()
    {
        return array(
            'PHP_AUTH_USER' => 'test',
            'PHP_AUTH_PW' => 'test',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        );
    }

    /**
     * @return Client
     */
    protected function getRestClient()
    {
        if (!$this->client) {
            $this->client = self::createClient([], $this->getConfig());
        }

        return $this->client;
    }

    public function testItShouldLimitRequestsAndRespondWithProperHeadersForRest()
    {
        $responseContentItem = '{"id":1,"name":"Item name 1","description":"Item description 1"}';
        $responseContentErrorMessage = $this->container->getParameter('smartapi.rate_response_message');

        $client = $this->getRestClient();

        $rateLimit = 2;
        for ($i = $rateLimit; $i >= 0; $i--) {
            $client->request('GET', '/api/rest/throttling/v1/item/1');
            $response = $client->getResponse();

            $remaining = $i - 1;
            if ($remaining >= 0) {
                $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode(), 'Response code is not correct.');
                $this->assertTrue($response->headers->contains('X-RateLimit-Limit', $rateLimit), sprintf('Response should contain header "%s" with value "%s".', 'X-RateLimit-Limit', $rateLimit));
                $this->assertTrue($response->headers->contains('X-RateLimit-Remaining', $remaining), sprintf('Response should contain header "%s" with value "%s".', 'X-RateLimit-Remaining', $remaining));
                $this->assertEquals($responseContentItem, $response->getContent(), 'Response should contain proper content.');
            } else {
                $this->assertEquals(Codes::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode(), 'Response code is not correct.');
                $this->assertTrue($response->headers->contains('X-RateLimit-Limit', $rateLimit), sprintf('Response should contain header "%s" with value "%s".', 'X-RateLimit-Limit', $rateLimit));
                $this->assertTrue($response->headers->contains('X-RateLimit-Remaining', 0), sprintf('Response should contain header "%s" with value "%s".', 'X-RateLimit-Remaining', 0));
                $this->assertEquals($responseContentErrorMessage, $response->getContent(), 'Response should contain proper content.');
            }
        }
        sleep(3);
    }
}
