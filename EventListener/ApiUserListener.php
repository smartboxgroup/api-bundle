<?php

namespace Smartbox\ApiBundle\EventListener;

use Smartbox\ApiBundle\Security\User\ApiUserInterface;
use Smartbox\ApiBundle\Services\ApiConfigurator;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Check that current user is allowed to use the flow.
 */
class ApiUserListener
{
    /**
     * @var TokenStorageInterface
     */
    private $storage;

    /**
     * ApiUserListener constructor.
     */
    public function __construct(TokenStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $name = $event->getRequest()->attributes->get(ApiConfigurator::METHOD_NAME);
        $token = $this->storage->getToken();

        if (!$event->isMasterRequest() || !$name || !$token || !($user = $token->getUser())) {
            return;
        }

        if ($user instanceof ApiUserInterface && !$user->hasFlow($name) && !$user->isAdmin()) {
            throw new AccessDeniedHttpException('You are not allowed to use this flow.');
        }
    }
}
