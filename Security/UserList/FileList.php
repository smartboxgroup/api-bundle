<?php

namespace Smartbox\ApiBundle\Security\UserList;

use Smartbox\ApiBundle\Security\User\ApiUser;
use Smartbox\ApiBundle\Security\User\ApiUserInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * Description of Class FileList.
 */
class FileList implements UserListInterface
{
    /**
     * Users configuration.
     *
     * @var array
     */
    private $config = [];

    /**
     * User list.
     *
     * @var ApiUserInterface[]
     */
    private $users = [];

    /**
     * FileList constructor.
     *
     * @param string $filename
     */
    public function __construct($filename)
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
                throw new \InvalidArgumentException("Unsupported config file format: \"{$file->getExtension()}\".", 400);
        }

        $this->validate($config);
    }

    /**
     * {@inheritdoc}
     */
    public function has($username)
    {
        return isset($this->config[$username]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($username)
    {
        if (!isset($this->config[$username])) {
            throw new \InvalidArgumentException("Unable to find \"$username\" user.");
        }

        if (!isset($this->users[$username])) {
            $info = $this->config[$username];
            $this->users[$username] = new ApiUser(
                $username,
                $info['password'],
                $info['is_admin'],
                $info['methods']
            );
        }

        return $this->users[$username];
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
                    ->end()
                ->end()
            ->end()
        ->end()
        ;

        $this->config = $treeBuilder->buildTree()->finalize($config)['users'];
    }
}
