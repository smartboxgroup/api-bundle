<?php

namespace Smartbox\ApiBundle\Services\Security;

use BeSimple\SoapServer\WsSecurityFilter;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class WSAuthProvider implements AuthenticationProviderInterface
{
    /** @var UserProviderInterface */
    private $userProvider;

    /** @var UserInterface */
    private $user;

    /** @var WsSecurityFilter */
    private $securityFilter;

    /**
     * Constructor.
     *
     * @param UserProviderInterface $userProvider
     * @param WsSecurityFilter|null $securityFilter
     */
    public function __construct(UserProviderInterface $userProvider, WsSecurityFilter $securityFilter = null)
    {
        $this->userProvider = $userProvider;

        if (null === $securityFilter) {
            $securityFilter = new WsSecurityFilter();
        }
        $this->securityFilter = $securityFilter;
    }

    public function loadUserByUsername($username)
    {
        $this->user = $this->userProvider->loadUserByUsername($username);
        if (!$this->user) {
            throw new AuthenticationException("The given user doesn't exist");
        }

        return $this->user->getPassword();
    }

    /**
     * Attempts to authenticate a TokenInterface object.
     *
     * @param TokenInterface $token The TokenInterface instance to authenticate
     *
     * @return TokenInterface An authenticated TokenInterface instance, never null
     *
     * @throws AuthenticationException if the authentication fails
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            throw new AuthenticationException('Token not supported');
        }

        if ($token instanceof WSToken) {
            $this->securityFilter->setUsernamePasswordCallback(array($this, 'loadUserByUsername'));
            $this->securityFilter->filterRequest($token->getSoapRequest());
        }

        if (!$this->user) {
            throw new AuthenticationException('Authentication failed');
        }

        $authToken = new WSToken($this->user->getRoles());
        $authToken->setUser($this->user);

        return $authToken;
    }

    /**
     * Checks whether this provider supports the given token.
     *
     * @param TokenInterface $token A TokenInterface instance
     *
     * @return bool true if the implementation supports the Token, false otherwise
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof WSToken;
    }
}
