<?php

namespace Smartbox\ApiBundle\Tests\Security\User;

use PHPUnit\Framework\TestCase;
use Smartbox\ApiBundle\Security\User\ApiProvider;
use Smartbox\ApiBundle\Security\User\ApiUserInterface;
use Smartbox\ApiBundle\Security\UserList\UserListInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;


/**
 * @group user-provider
 */
class ApiProviderTest extends TestCase
{
    /**
     * @var UserListInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $list;

    /**
     * @var ApiProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->list = $this->getMockBuilder(UserListInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ApiProvider($this->list);
    }

    public function testRefreshUser()
    {
        $prophecy = $this->prophesize();
        $prophecy->willImplement(ApiUserInterface::class);
        $prophecy->willImplement(UserInterface::class);
        /* @noinspection PhpUndefinedMethodInspection */
        $prophecy->getUsername()->willReturn('foo');

        /** @var UserInterface $user */
        $user = $prophecy->reveal();

        $this->list->expects($this->once())->method('has')->with('foo')->willReturn(true);
        $this->list->expects($this->once())->method('get')->with('foo')->willReturn($user);

        $this->assertSame($user, $this->provider->refreshUser($user));
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function testUnsupportedUserException()
    {
        /** @var UserInterface $user */
        $user = $this->getMockBuilder(UserInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->setExpectedException(UnsupportedUserException::class);
        //Mel to fix
//        $this->expectExceptionMessage(sprintf('Instances of "%s" are not supported.', \get_class($user)));

        $this->provider->refreshUser($user);
    }

    public function testSupportsClass()
    {
        //$mock = $this->createMock(ApiUserInterface::class);
        $mock = $this->getMockBuilder(ApiUserInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertTrue(
            $this->provider->supportsClass(\get_class($mock)),
            'Instance of ApiUserInterface should be supported.'
        );

        $this->assertFalse(
            $this->provider->supportsClass('stdClass'),
            'Dummy class should not be supported.'
        );

        return $mock;
    }

    public function testLoadUserByUsername()
    {
//        $user = $this->createMock(ApiUserInterface::class);
        $user = $this->getMockBuilder(ApiUserInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->list->expects($this->once())->method('has')->with('foo')->willReturn(true);
        $this->list->expects($this->once())->method('get')->with('foo')->willReturn($user);

        $this->assertSame($user, $this->provider->loadUserByUsername('foo'));
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @expectedExceptionMessage Username "404" does not exist.
     */
    public function testUserNotFound()
    {
        $this->provider->loadUserByUsername('404');
    }
}
