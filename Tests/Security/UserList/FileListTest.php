<?php

namespace Smartbox\ApiBundle\Tests\Security\UserList;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Smartbox\ApiBundle\Security\User\ApiUser;
use Smartbox\ApiBundle\Security\UserList\FileList;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * @group user-provider
 */
class FileListTest extends TestCase
{
    /**
     * @var string
     */
    private static $fixtureDir;

    /**
     * @var ArrayAdapter
     */
    private $cache;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->cache = new ArrayAdapter();
    }

    /**
     * @param string $ext
     *
     * @dataProvider provideSupportedExtensions
     */
    public function testGet($ext)
    {
        $list = $this->getFileList("valid_config.$ext", "passwords.$ext");

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
     * @param string $ext
     *
     * @dataProvider provideSupportedExtensions
     */
    public function testHas($ext)
    {
        $list = $this->getFileList("valid_config.$ext", "passwords.$ext");

        $this->assertTrue($list->has('admin'), 'Admin should be here.');
        $this->assertFalse($list->has('zboob'), 'Zboob should not be here.');
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testBuildCache()
    {
        $key = sprintf('%s.admin', FileList::CACHE_PREFIX);

        $this->assertFalse($this->cache->hasItem($key), "Key \"$key\" should not exists before cache building.");
        $this->getFileList('valid_config.json', 'passwords.json')->buildCache();
        $this->assertTrue($this->cache->hasItem($key), "Key \"$key\" should exists after cache building.");
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Password is missing for user "box_picker".
     */
    public function testMissingPassword()
    {
        $this->getFileList('valid_config.json', 'passwords.yml')->buildCache();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidFilename()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        // Mel to fix
        //        $this->expectExceptionMessage("Invalid config file provided: \"{$this->getFixtureDir()}/I'm invalid\".");
        $this->getFileList('I\'m invalid', 'And I know it')->buildCache();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported config file format: "xml".
     */
    public function testInvalidExtension()
    {
        $this->getFileList('invalid.xml', 'passwords.json');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to fetch "admin" user: "Something happened".
     */
    public function testCacheFailure()
    {
        $this->cache = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->cache->expects($this->once())
            ->method('getItem')
            ->willThrowException(new \InvalidArgumentException('Something happened'));

        $this->getFileList('valid_config.json', 'passwords.json')->get('admin');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unable to find "foo_404" user.
     */
    public function testUnknownUser()
    {
        $this->getFileList('valid_config.json', 'passwords.json')->get('foo_404');
    }

    /**
     * @return array
     */
    public function provideSupportedExtensions()
    {
        return [
            'JSON' => ['json'],
            'YAML' => ['yml'],
        ];
    }

    /**
     * @param string $users
     * @param string $password
     *
     * @return FileList
     */
    private function getFileList($users, $password)
    {
        return new FileList("{$this->getFixtureDir()}/$users", "{$this->getFixtureDir()}/$password", $this->cache);
    }

    /**
     * @return string
     */
    private function getFixtureDir()
    {
        if (!static::$fixtureDir) {
            static::$fixtureDir = realpath(dirname(__DIR__).'/../Fixtures/UserProvider');
        }

        return static::$fixtureDir;
    }
}
