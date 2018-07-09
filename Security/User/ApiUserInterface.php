<?php

namespace Smartbox\ApiBundle\Security\User;

/**
 * API Bundle User interface.
 */
interface ApiUserInterface
{
    /**
     * The list of flows that user can access (Ex: ['getBoxes', 'fooBar']).
     *
     * @return array
     */
    public function getFlows();

    /**
     * Is the user allowed to use this flow?
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasFlow($name);

    /**
     * If true, grant access to every flows.
     *
     * @return bool
     */
    public function isAdmin();
}
