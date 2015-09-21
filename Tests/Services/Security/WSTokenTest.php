<?php

namespace Smartbox\ApiBundle\Tests\Services\Security;

use Smartbox\ApiBundle\Services\Security\WSToken;

class WSTokenTest extends \PHPUnit_Framework_TestCase
{
    /** @var  WSToken */
    private $token;

    public function setup()
    {
        $this->token = new WSToken();
    }

    /**
     * @test
     */
    public function it_should_set_and_get_a_soap_request()
    {
        $soapRequest = $this->getMockBuilder('\BeSimple\SoapCommon\SoapRequest')->getMock();
        $this->token->setSoapRequest($soapRequest);
        $this->assertSame($soapRequest, $this->token->getSoapRequest());
    }

    /**
     * @test
     */
    public function it_should_get_credentials()
    {
        $soapRequest = $this->getMockBuilder('\BeSimple\SoapCommon\SoapRequest')->getMock();
        $this->token->setSoapRequest($soapRequest);
        $this->assertSame($soapRequest, $this->token->getCredentials());
    }

    /**
     * @test
     */
    public function it_should_be_authenticated_if_roles_are_given()
    {
        $token = new WSToken(['some_role']);
        $this->assertTrue($token->isAuthenticated());
    }
}
