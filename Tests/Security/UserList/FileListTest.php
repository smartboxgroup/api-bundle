<?php

namespace Smartbox\ApiBundle\Tests\Security\UserList;

use PHPUnit\Framework\TestCase;
use Smartbox\ApiBundle\Security\User\ApiUser;
use Smartbox\ApiBundle\Security\UserList\FileList;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

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
     * @var ArrayAdapter
     */
    private $cache;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->fixtureDir = realpath(dirname(__DIR__).'/../Fixtures/UserProvider');
        $this->cache = new ArrayAdapter();
    }

    /**
     * @param string $ext
     *
     * @dataProvider provideSupportedExtensions
     */
    public function testGet($ext)
    {
        $this->list = new FileList("{$this->fixtureDir}/valid_config.$ext", $this->cache);

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
        $this->list = new FileList("{$this->fixtureDir}/valid_config.$ext", $this->cache);

        $this->assertTrue($this->list->has('admin'), 'Admin should be here.');
        $this->assertFalse($this->list->has('zboob'), 'Zboob should not be here.');
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testBuildCache()
    {
        $key = sprintf('%s.admin', FileList::CACHE_PREFIX);
        $this->assertFalse($this->cache->hasItem($key), "Key \"$key\" should not exists before cache building.");
        (new FileList("{$this->fixtureDir}/valid_config.json", $this->cache))->buildCache();
        $this->assertTrue($this->cache->hasItem($key), "Key \"$key\" should exists after cache building.");
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
}
