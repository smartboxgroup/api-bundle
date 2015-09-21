<?php

namespace Smartbox\ApiBundle\Tests\Services\Security;

use Smartbox\ApiBundle\Services\Security\WSAuthProvider;
use Smartbox\ApiBundle\Tests\Fixtures\Soap\FakeCallbackSecurityFilter;

class WSAuthProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\Security\Core\User\UserProviderInterface|\PHPUnit_Framework_MockObject_MockObject $userProvider
     */
    private $userProvider;

    /** @var FakeCallbackSecurityFilter */
    private $securityFilter;

    /** @var  WSAuthProvider */
    private $authProvider;

    public function setup()
    {
        $this->userProvider = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserProviderInterface')
            ->getMock()
        ;

        $this->securityFilter = new FakeCallbackSecurityFilter();

        $this->authProvider = new WSAuthProvider($this->userProvider, $this->securityFilter);
    }

    /**
     * @test
     */
    public function it_should_load_user_by_username()
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
     * @test
     * @expectedException  \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function it_should_fail_loading_user_when_user_cannot_be_found()
    {
        $username = 'John Doe';
        $this->userProvider
            ->method('loadUserByUsername')
            ->with($username)
            ->will($this->returnValue(null))
        ;

        $this->authProvider->loadUserByUsername($username);
    }

    /**
     * @test
     */
    public function it_should_authenticate_a_valid_token()
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
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage The given user doesn't exist
     */
    public function it_should_not_authenticate_an_invalid_token()
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
     * @test
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage Token not supported
     */
    public function it_should_throw_exception_with_unsupported_tokens()
    {
        /** @var \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token */
        $token = $this->getMockBuilder('\Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMock();

        $this->authProvider->authenticate($token);
    }

    /**
     * @test
     */
    public function it_should_support_ws_tokens()
    {
        /** @var \Smartbox\ApiBundle\Services\Security\WSToken $token */
        $token = $this->getMockBuilder('\Smartbox\ApiBundle\Services\Security\WSToken')
            ->getMock();

        $this->assertTrue($this->authProvider->supports($token));
    }

    /**
     * @test
     */
    public function it_should_not_support_other_tokens()
    {
        /** @var \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token */
        $token = $this->getMockBuilder('\Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMock();

        $this->assertFalse($this->authProvider->supports($token));
    }
}
