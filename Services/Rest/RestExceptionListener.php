<?php
/**
 * Created by PhpStorm.
 * User: alberto.rodrigo
 * Date: 13/01/2016
 * Time: 16:04
 */

namespace Smartbox\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\EventListener\ExceptionListener;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class RestExceptionListener extends ExceptionListener
{

    /**
     * Prevent non rest calls from not being logged
     * @param GetResponseForExceptionEvent $event
     * @throws \Exception
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        $apiMode = $request->get('api');
        if ($apiMode !== 'rest') {
            return;
        }
        parent::onKernelException($event);
    }
}