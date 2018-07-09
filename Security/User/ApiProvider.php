<?php

namespace Smartbox\ApiBundle\Security\User;

use Smartbox\ApiBundle\Security\UserList\UserListInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Smartbox API bundle User Provider.
 *
 * @see https://symfony.com/doc/current/security/custom_provider.html
 */
class ApiProvider implements UserProviderInterface
{
    /**
     * @var UserListInterface
     */
    private $list;

    /**
     * ApiProvider constructor.
     *
     * @param UserListInterface $list
     */
    public function __construct(UserListInterface $list)
    {
        $this->list = $list;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        if (!$this->list->has($username)) {
            throw new UsernameNotFoundException("Username \"$username\" does not exist.");
        }

        return $this->list->get($username);
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        $class = \get_class($user);

        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException("Instances of \"$class\" are not supported.");
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return \in_array(ApiUserInterface::class, \class_implements($class));
    }
}
