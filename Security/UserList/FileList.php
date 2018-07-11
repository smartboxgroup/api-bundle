<?php

namespace Smartbox\ApiBundle\Security\UserList;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Smartbox\ApiBundle\Security\User\ApiUser;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Yaml\Yaml;

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

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * FileList constructor.
     *
     * @param string                 $filename
     * @param CacheItemPoolInterface $cache
     */
    public function __construct($filename, CacheItemPoolInterface $cache)
    {
        if (!\is_file($filename)) {
            throw new \InvalidArgumentException("Invalid config file provided: \"$filename\".", 404);
        }

        $file = new \SplFileInfo($filename);

        switch (strtolower($file->getExtension())) {
            case 'yml':
            case 'yaml':
                $config = Yaml::parse(file_get_contents($file->getRealPath()));
                break;

            case 'json':
                $config = json_decode(file_get_contents($file->getRealPath()), true);
                break;

            default:
                throw new \InvalidArgumentException(
                    "Unsupported config file format: \"{$file->getExtension()}\".", 400
                );
        }

        $this->validate($config);
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
        if (!isset($this->config['users'][$username])) {
            throw new \InvalidArgumentException("Unable to find \"$username\" user.");
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

            $item->set(new ApiUser($username, $info['password'], $info['is_admin'], $methods));
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

    private function validate(array $config)
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('api');
        $rootNode
            ->children()
            ->arrayNode('users')
                ->useAttributeAsKey('username')
                ->requiresAtLeastOneElement()
                ->prototype('array')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('password')->isRequired()->end()
                        ->booleanNode('is_admin')->defaultFalse()->end()
                        ->arrayNode('methods')
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('groups')
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end() //users
            ->arrayNode('groups')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('methods')
                            ->defaultValue([])
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end() // groups
        ->end()
        ;

        $this->config = $treeBuilder->buildTree()->finalize($config);
    }
}
