<?php

namespace Smartbox\ApiBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Standard API user.
 */
class ApiUser implements UserInterface, ApiUserInterface
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var bool
     */
    protected $admin;

    /**
     * @var array
     */
    protected $flows;

    /**
     * ApiUser constructor.
     *
     * @param string $username
     * @param string $password
     * @param bool   $admin
     * @param array  $methods
     */
    public function __construct($username, $password, $admin = false, array $methods = [])
    {
        $this->username = $username;
        $this->password = $password;
        $this->admin = $admin;
        $this->flows = $methods;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function isAdmin()
    {
        return $this->admin;
    }

    /**
     * {@inheritdoc}
     */
    public function getFlows()
    {
        return $this->flows;
    }

    /**
     * {@inheritdoc}
     */
    public function hasFlow($name)
    {
        return \in_array($name, $this->flows);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return [$this->isAdmin() ? 'ROLE_ADMIN' : 'ROLE_USER'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        $this->password = null;
    }
}
