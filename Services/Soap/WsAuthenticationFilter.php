<?php

namespace Smartbox\ApiBundle\Services\Soap;

use BeSimple\SoapCommon\SoapRequest as CommonSoapRequest;
use BeSimple\SoapServer\WsSecurityFilter;
use Smartbox\ApiBundle\Services\Security\WSToken;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class WsAuthenticationFilter.
 */
class WsAuthenticationFilter extends WsSecurityFilter
{
    /** @var AuthenticationProviderInterface */
    protected $authProvider;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * WsAuthenticationFilter constructor.
     *
     * @param AuthenticationProviderInterface $authProvider
     * @param TokenStorageInterface           $tokenStorage
     */
    public function __construct(
        AuthenticationProviderInterface $authProvider,
        TokenStorageInterface $tokenStorage
    ) {
        $this->authProvider = $authProvider;
        $this->tokenStorage = $tokenStorage;
    }

    public function filterRequest(CommonSoapRequest $request)
    {
        $token = new WSToken();
        $token->setSoapRequest($request);

        try {
            $authToken = $this->authProvider->authenticate($token);
            $this->tokenStorage->setToken($authToken);
        } catch (AuthenticationException $ex) {
            throw new \SoapFault('wsse:FailedAuthentication', 'Authentication failed');
        }

        return $authToken;
    }
}
