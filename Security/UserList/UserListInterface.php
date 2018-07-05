<?php

namespace Smartbox\ApiBundle\Security\UserList;

/**
 * Interface used to provide user details.
 */
interface UserListInterface
{
    /**
     * Does the list contain this username?
     *
     * @param string $username
     *
     * @return bool
     */
    public function has($username);

    /**
     * Get the user matching the following username.
     *
     * @param string $username
     *
     * @return ApiUserInterface
     */
    public function get($username);
}
