<?php

namespace Smartbox\ApiBundle\Services\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class WSToken extends AbstractToken
{
    protected $soapRequest;

    public function __construct(array $roles = [])
    {
        parent::__construct($roles);

        // If the user has roles, consider it authenticated
        $this->setAuthenticated(count($roles) > 0);
    }

    /**
     * @return mixed
     */
    public function getSoapRequest()
    {
        return $this->soapRequest;
    }

    /**
     * @param mixed $soapRequest
     */
    public function setSoapRequest($soapRequest)
    {
        $this->soapRequest = $soapRequest;
    }

    /**
     * Returns the user credentials.
     *
     * @return mixed The user credentials
     */
    public function getCredentials()
    {
        return $this->soapRequest;
    }
}
