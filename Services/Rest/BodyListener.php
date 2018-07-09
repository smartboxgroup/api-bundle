<?php

namespace Smartbox\ApiBundle\Services\Rest;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class BodyListener extends \FOS\RestBundle\EventListener\BodyListener
{
    /**
     * Core request handler.
     *
     * @param GetResponseEvent $event
     *
     * @throws BadRequestHttpException
     * @throws UnsupportedMediaTypeHttpException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ('rest' == $event->getRequest()->get('api')) {
            parent::onKernelRequest($event);
        }
    }
}
