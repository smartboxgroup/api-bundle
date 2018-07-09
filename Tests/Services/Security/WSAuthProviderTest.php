<?php

namespace Smartbox\ApiBundle\Tests\Services\Security;

use Smartbox\ApiBundle\Services\Security\WSAuthProvider;
use Smartbox\ApiBundle\Tests\Fixtures\Soap\FakeCallbackSecurityFilter;

class WSAuthProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Symfony\Component\Security\Core\User\UserProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userProvider;

    /** @var FakeCallbackSecurityFilter */
    private $securityFilter;

    /** @var WSAuthProvider */
    private $authProvider;

    public function setup()
    {
        $this->userProvider = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserProviderInterface')
            ->getMock()
        ;

        $this->securityFilter = new FakeCallbackSecurityFilter();

        $this->authProvider = new WSAuthProvider($this->userProvider, $this->securityFilter);
    }

    public function testItShouldLoadUserByUsername()
    {
        $username = 'John Doe';
        $password = 'love';
        /** @var \Symfony\Component\Security\Core\User\UserInterface|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMockBuilder('\Symfony\Component\Security\Core\User\UserInterface')
            ->getMock()
        ;

        $user->method('getPassword')
            ->will($this->returnValue($password))
        ;

        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($username)
            ->will($this->returnValue($user))
        ;

        $this->assertEquals($password, $this->authProvider->loadUserByUsername($username));
    }

    /**
     * @expectedException  \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function testItShouldFailLoadingUserWhenUserCannotBeFound()
    {
        $username = 'John Doe';
        $this->userProvider
            ->method('loadUserByUsername')
            ->with($username)
            ->will($this->returnValue(null))
        ;

        $this->authProvider->loadUserByUsername($username);
    }

    public function testItShouldAuthenticateAValidToken()
    {
        $username = 'John Doe';
        $password = 'love';
        /** @var \Symfony\Component\Security\Core\User\UserInterface|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->getMockBuilder('\Symfony\Component\Security\Core\User\UserInterface')
            ->getMock()
        ;

        $user->method('getRoles')
            ->will($this->returnValue(['someRole']))
        ;

        $user->method('getPassword')
            ->will($this->returnValue($password))
        ;

        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($username)
            ->will($this->returnValue($user))
        ;

        /** @var \Smartbox\ApiBundle\Services\Security\WSToken|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMockBuilder('\Smartbox\ApiBundle\Services\Security\WSToken')
            ->getMock()
        ;

        $soapRequest = $this->getMockBuilder('\BeSimple\SoapCommon\SoapRequest')->getMock();
        $token->method('getSoapRequest')
            ->will($this->returnValue($soapRequest))
        ;

        $this->securityFilter->setCallbackParameters([$username]);

        $authenticatedToken = $this->authProvider->authenticate($token);

        $this->assertTrue($authenticatedToken->isAuthenticated());
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage The given user doesn't exist
     */
    public function testItShouldNotAuthenticateAnInvalidToken()
    {
        $username = 'John Doe';

        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($username)
            ->will($this->returnValue(null))
        ;

        /** @var \Smartbox\ApiBundle\Services\Security\WSToken|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMockBuilder('\Smartbox\ApiBundle\Services\Security\WSToken')
            ->getMock()
        ;

        $soapRequest = $this->getMockBuilder('\BeSimple\SoapCommon\SoapRequest')->getMock();
        $token->method('getSoapRequest')
            ->will($this->returnValue($soapRequest))
        ;

        $this->securityFilter->setCallbackParameters([$username]);

        $this->authProvider->authenticate($token);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage Token not supported
     */
    public function testItShouldThrowExceptionWithUnsupportedTokens()
    {
        /** @var \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token */
        $token = $this->getMockBuilder('\Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMock();

        $this->authProvider->authenticate($token);
    }

    public function testItShouldSupportWsTokens()
    {
        /** @var \Smartbox\ApiBundle\Services\Security\WSToken $token */
        $token = $this->getMockBuilder('\Smartbox\ApiBundle\Services\Security\WSToken')
            ->getMock();

        $this->assertTrue($this->authProvider->supports($token));
    }

    public function testItShouldNotSupportOtherTokens()
    {
        /** @var \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token */
        $token = $this->getMockBuilder('\Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMock();

        $this->assertFalse($this->authProvider->supports($token));
    }
}
