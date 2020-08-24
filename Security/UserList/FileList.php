<?php

namespace Smartbox\ApiBundle\Security\UserList;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Smartbox\ApiBundle\Security\User\ApiUser;

/**
 * File based user list.
 */
class FileList implements UserListInterface
{
    const CACHE_PREFIX = 'smartapi.user_cache';

    /**
     * Users configuration.
     *
     * @var array
     */
    private $config = [];

    private $passwords = [];

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * FileList constructor.
     *
     * @param $config
     * @param $passwords
     */
    public function __construct($config, $passwords, CacheItemPoolInterface $cache)
    {
        $this->config = $config;
        $this->passwords = $passwords;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function has($username)
    {
        return isset($this->config['users'][$username]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($username)
    {
        if (!$this->has($username)) {
            throw new \InvalidArgumentException("Unable to find \"$username\" user.");
        }

        if (!key_exists($username, $this->passwords)) {
            throw new \InvalidArgumentException("Password is missing for user \"$username\".");
        }

        try {
            $item = $this->cache->getItem(sprintf('%s.%s', static::CACHE_PREFIX, preg_replace('/\W/', '_', $username)));
        } catch (InvalidArgumentException $e) {
            throw new \RuntimeException("Unable to fetch \"$username\" user: \"{$e->getMessage()}\".");
        }

        if (!$item->isHit()) {
            $info = $this->config['users'][$username];
            $methods = $info['methods'];

            foreach ($info['groups'] as $group) {
                if (!isset($this->config['groups'][$group])) {
                    throw new \InvalidArgumentException("Undefined group \"$group\" for user \"$username\".");
                }

                $methods = array_unique(array_merge($methods, $this->config['groups'][$group]['methods']));
            }
            sort($methods);

            $item->set(new ApiUser($username, $this->passwords[$username], $info['is_admin'], $methods));
            $this->cache->save($item);
        }

        return $item->get();
    }

    public function buildCache()
    {
        foreach (\array_keys($this->config['users']) as $username) {
            $this->get($username);
        }
    }
}
