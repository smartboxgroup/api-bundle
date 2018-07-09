<?php

namespace Smartbox\ApiBundle\Tests\Services\Security;

use Smartbox\ApiBundle\Services\Security\WSToken;

class WSTokenTest extends \PHPUnit_Framework_TestCase
{
    /** @var WSToken */
    private $token;

    public function setup()
    {
        $this->token = new WSToken();
    }

    public function testItShouldSetAndGetASoapRequest()
    {
        $soapRequest = $this->getMockBuilder('\BeSimple\SoapCommon\SoapRequest')->getMock();
        $this->token->setSoapRequest($soapRequest);
        $this->assertSame($soapRequest, $this->token->getSoapRequest());
    }

    public function testItShouldGetCredentials()
    {
        $soapRequest = $this->getMockBuilder('\BeSimple\SoapCommon\SoapRequest')->getMock();
        $this->token->setSoapRequest($soapRequest);
        $this->assertSame($soapRequest, $this->token->getCredentials());
    }

    public function testItShouldBeAuthenticatedIfRolesAreGiven()
    {
        $token = new WSToken(['some_role']);
        $this->assertTrue($token->isAuthenticated());
    }
}
