<?php

namespace Smartbox\ApiBundle\Services\Rest;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;

class RestExceptionListener extends ExceptionListener
{
    /**
     * Prevent non rest calls from not being logged.
     *
     * @throws \Exception
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        $apiMode = $request->get('api');

        if ('rest' == $apiMode || empty($apiMode)) {
            parent::onKernelException($event);
        }
    }
}
