<?php

namespace Smartbox\ApiBundle\Tests\EventListener;

use FOS\RestBundle\Util\Codes;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ThrottlingListenerTest.
 *
 * @group trotling
 */
class ThrottlingListenerTest extends WebTestCase
{
    const REST_RATE_LIMIT_KEY = 'throttling_v1:getItem.test';

    /** @var Client */
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

        if ('redis' === gethostbyname('redis')) {
            $this->markTestSkipped('DNS "redis" is not available');
        }
    }

    public function getConfig()
    {
        return [
            'PHP_AUTH_USER' => 'test',
            'PHP_AUTH_PW' => 'test',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
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
        $responseContentItem = '{"id":1,"name":"Item name 1","description":"Item description 1","type":"Item type 1"}';
        $responseContentErrorMessage = $this->container->getParameter('smartapi.rate_response_message');

        $client = $this->getRestClient();
        $client->getContainer()->get('noxlogic_rate_limit.rate_limit_service')
            ->resetRate(static::REST_RATE_LIMIT_KEY);

        $rateLimit = 2;
        for ($i = 0; $i <= $rateLimit; ++$i) {
            $client->request('GET', '/api/rest/throttling/v1/item/1');
            $response = $client->getResponse();

            $remaining = $rateLimit - (1 + $i);
            $expectedHeaders = [
                'X-RateLimit-Limit' => $rateLimit,
                'X-RateLimit-Remaining' => $remaining,
            ];

            if ($remaining >= 0) {
                $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode(), 'Response code is not correct.');
                $this->assertEquals($responseContentItem, $response->getContent(), 'Response should contain proper content.');
            } else {
                $expectedHeaders['X-RateLimit-Remaining'] = 0;
                $this->assertEquals(Codes::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode(), 'Response code is not correct.');
                $this->assertEquals($responseContentErrorMessage, $response->getContent(), 'Response should contain proper content.');
            }

            foreach ($expectedHeaders as $header => $value) {
                $this->assertTrue($response->headers->has($header), "Response should contain header \"$header\".");
                $this->assertEquals($value, $response->headers->get($header), "Response header \"$header\" should be $value.");
            }
        }
    }

    public function testItShouldLimitRequestsAndRespondWithProperHeadersForSoap()
    {
        $this->markTestSkipped('Cannot modify header information - headers already sent by (output started at /vagrant/bundles/api-bundle/vendor/phpunit/phpunit/src/Util/Printer.php:134)');
        $responseContentErrorMessage =
            \sprintf(
'<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><SOAP-ENV:Body><SOAP-ENV:Fault><faultcode>Sender</faultcode><faultstring>%s</faultstring><detail/></SOAP-ENV:Fault></SOAP-ENV:Body></SOAP-ENV:Envelope>
',
                $this->container->getParameter('smartapi.rate_response_message')
        );

        $client = $this->getRestClient();

        $client->request('GET', '/api/soap/throttling_v1');

        $rateLimit = 2;
        $payload =
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:thr="http://localhost/api/soap/throttling_v1/">
                <soapenv:Header>
                    <wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                        <wsse:UsernameToken >
                            <wsse:Username>test</wsse:Username>
                            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">test</wsse:Password>
                            <wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">%s</wsse:Nonce>
                            <wsu:Created>2016-07-05T12:23:12.019Z</wsu:Created>
                        </wsse:UsernameToken>
                    </wsse:Security>
                   </soapenv:Header>
                   <soapenv:Body>
                      <thr:getItem>
                         <id>1</id>
                      </thr:getItem>
                   </soapenv:Body>
            </soapenv:Envelope>';

        for ($i = $rateLimit; $i >= 0; --$i) {
            $prefix = \gethostname();
            $nonce = \base64_encode(\substr(\md5(\uniqid($prefix.'_', true)), 0, 16));

            $client->request(
                'POST',
                '/api/soap/throttling_v1',
                [],
                [],
                ['CONTENT_TYPE' => 'application/xml'],
                \sprintf($payload, $nonce)
            );
            $response = $client->getResponse();

            $remaining = $i - 1;
            if ($remaining >= 0) {
                $this->assertEquals(Codes::HTTP_OK, $response->getStatusCode(), 'Response code is not correct.');
                $this->assertTrue($response->headers->contains('x-ratelimit-limit', $rateLimit), \sprintf('Response should contain header "%s" with value "%s".', 'X-RateLimit-Limit', $rateLimit));
                $this->assertTrue($response->headers->contains('x-ratelimit-remaining', $remaining), \sprintf('Response should contain header "%s" with value "%s".', 'X-RateLimit-Remaining', $remaining));
            } else {
                $this->assertEquals(Codes::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode(), 'Response code is not correct.');
                $this->assertTrue($response->headers->contains('x-ratelimit-limit', $rateLimit), \sprintf('Response should contain header "%s" with value "%s".', 'X-RateLimit-Limit', $rateLimit));
                $this->assertTrue($response->headers->contains('x-ratelimit-remaining', 0), \sprintf('Response should contain header "%s" with value "%s".', 'X-RateLimit-Remaining', 0));
                $this->assertEquals($responseContentErrorMessage, $response->getContent(), 'Response should contain proper content.');
            }
        }
    }
}
