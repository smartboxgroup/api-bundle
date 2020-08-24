<?php

namespace Smartbox\ApiBundle\EventListener;

use Predis\Client;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Class RedisConnectionListener.
 */
class RedisConnectionListener
{
    /** @var Client */
    private $redis;

    /**
     * RedisConnectionListener constructor.
     */
    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Closes the current connection with redis (if open).
     */
    private function doDestroy()
    {
        $this->redis->disconnect();
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->doDestroy();
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $this->doDestroy();
    }
}
