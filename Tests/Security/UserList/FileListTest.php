<?php

namespace Smartbox\ApiBundle\Tests\Security\UserList;

use Smartbox\ApiBundle\DependencyInjection\SmartboxApiExtension;
use Smartbox\ApiBundle\Security\User\ApiUser;
use Smartbox\ApiBundle\Security\UserList\FileList;
use Smartbox\ApiBundle\Tests\BaseKernelTestCase;

/**
 * @group user-provider
 */
class FileListTest extends BaseKernelTestCase
{
    /**
     * @var string
     */
    private static $fixtureDir;

    /**
     * Basic get test. Users should be able to be retrieved with valid configuration.
     **/
    public function testGet()
    {
        $list = $this->getFileList();

        /** @var ApiUser $user */
        $user = $list->get('regular');
        $this->assertInstanceOf(ApiUser::class, $user);
        $this->assertSame('regular', $user->getUsername());
        $this->assertSame('P4$$W0rd', $user->getPassword());
        $this->assertFalse($user->isAdmin(), 'User should not be admin');
        $this->assertEquals(['fooBar', 'getBox', 'getBoxes'], $user->getFlows());

        $this->assertTrue($list->get('admin')->isAdmin(), 'Admin should be admin');
        $this->assertEmpty($list->get('useless')->getFlows(), 'User should not have any flows');
    }

    /**
     * Basic has test. Service should tell if the user is present or not.
     */
    public function testHas()
    {
        $list = $this->getFileList();

        $this->assertTrue($list->has('admin'), 'Admin should be here.');
        $this->assertFalse($list->has('zboob'), 'Zboob should not be here.');
    }

    /**
     * Test if an exception is raised when an user cannot be found.
     */
    public function testUnknownUser()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find "foo_404" user.');
        $this->getFileList()->get('foo_404');
    }

    /**
     * @return FileList
     */
    private function getFileList()
    {
        return $this->getContainer()->get(SmartboxApiExtension::SERVICE_ID_FILE_LIST);
    }

    /**
     * @return string
     */
    private function getFixtureDir()
    {
        if (!static::$fixtureDir) {
            static::$fixtureDir = \realpath(\dirname(__DIR__).'/../Fixtures/UserProvider');
        }

        return static::$fixtureDir;
    }
}
