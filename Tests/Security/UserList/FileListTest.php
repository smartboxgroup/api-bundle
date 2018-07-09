<?php

namespace Smartbox\ApiBundle\Tests\Security\UserList;

use PHPUnit\Framework\TestCase;
use Smartbox\ApiBundle\Security\User\ApiUser;
use Smartbox\ApiBundle\Security\UserList\FileList;

/**
 * @group user-provider
 */
class FileListTest extends TestCase
{
    /**
     * @var string
     */
    private $fixtureDir;

    /**
     * @var FileList
     */
    private $list;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->fixtureDir = realpath(dirname(__DIR__).'/../Fixtures/UserProvider');
    }

    /**
     * @param string $ext
     *
     * @dataProvider provideSupportedExtensions
     */
    public function testGet($ext)
    {
        $this->list = new FileList("{$this->fixtureDir}/valid_config.$ext");

        /** @var ApiUser $user */
        $user = $this->list->get('regular');
        $this->assertInstanceOf(ApiUser::class, $user);
        $this->assertSame('regular', $user->getUsername());
        $this->assertSame('P4$$W0rd', $user->getPassword());
        $this->assertFalse($user->isAdmin(), 'User should not be admin');
        $this->assertEquals(['fooBar', 'getBox', 'getBoxes'], $user->getFlows());

        $this->assertTrue($this->list->get('admin')->isAdmin(), 'Admin should be admin');
        $this->assertEmpty($this->list->get('useless')->getFlows(), 'User should not have any flows');
    }

    /**
     * @param string $ext
     *
     * @dataProvider provideSupportedExtensions
     */
    public function testHas($ext)
    {
        $this->list = new FileList("{$this->fixtureDir}/valid_config.$ext");

        $this->assertTrue($this->list->has('admin'), 'Admin should be here.');
        $this->assertFalse($this->list->has('zboob'), 'Zboob should not be here.');
    }

    /**
     * @return array
     */
    public function provideSupportedExtensions()
    {
        return [
            'JSON' => ['json'],
            'YAML' => ['yaml'],
        ];
    }
}
