<?php

namespace Smartbox\ApiBundle\EventListener;

use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Predis\Connection\ConnectionException;
use Psr\Log\LoggerAwareTrait;
use Smartbox\CoreBundle\Utils\Monolog\Formatter\JMSSerializerFormatter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthKeyGenerateListener
{
    use LoggerAwareTrait;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param GenerateKeyEvent $event
     */
    public function onGenerateKey(GenerateKeyEvent $event)
    {
        try {
            if ($this->tokenStorage && ($token = $this->tokenStorage->getToken()) && $token->getUsername()) {
                $event->addToKey($token->getUsername());
            }
        } catch (ConnectionException $e) {
            $this->logger->error('Redis service is down.', ['message' => $e->getMessage(), JMSSerializerFormatter::_USE_JSON_ENCODE => true]);
        }
    }
}
